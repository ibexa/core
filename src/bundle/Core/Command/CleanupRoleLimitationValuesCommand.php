<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Bundle\Core\Command;

use Ibexa\Bundle\Core\Command\Role\DanglingLimitationValues;
use Ibexa\Contracts\Core\Limitation\Type;
use Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException;
use Ibexa\Contracts\Core\Repository\Repository;
use Ibexa\Contracts\Core\Repository\Values\Translation\Message;
use Ibexa\Contracts\Core\Repository\Values\User\Limitation;
use Ibexa\Contracts\Core\Repository\Values\User\Policy;
use Ibexa\Contracts\Core\Repository\Values\User\PolicyDraft;
use Ibexa\Contracts\Core\Repository\Values\User\Role;
use Ibexa\Contracts\Core\Repository\Values\User\RoleDraft;
use Ibexa\Core\Base\Exceptions\BadStateException;
use Ibexa\Core\Base\Exceptions\InvalidArgumentType;
use Ibexa\Core\Base\Exceptions\NotFound\LimitationNotFoundException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Throwable;

/**
 * @internal
 */
final class CleanupRoleLimitationValuesCommand extends Command
{
    public const BEFORE_RUNNING_HINTS = <<<EOT
<error>Before you continue:</error>
- Make sure to back up your database.
- Take the installation offline. The database should not be modified while the script is being executed.
- Run this command in production environment using <info>--env=prod</info>
EOT;

    private const DEFAULT_REPOSITORY_USER = 'admin';

    /**
     * This is how Limitation types word a value whose entity is gone. A value rejected for any other
     * reason is malformed rather than dangling and must not be removed, so the phrase is matched at
     * the end of the message rather than anywhere in it.
     */
    private const MISSING_ENTITY_SUFFIX = 'does not exist in the backend';

    private const MISSING_ENTITY_MESSAGES = [
        'Invalid SiteAccess value',
        'translation(s) do not exist',
    ];

    private Repository $repository;

    public function __construct(Repository $repository)
    {
        $this->repository = $repository;

        parent::__construct();
    }

    protected function configure(): void
    {
        $beforeRunningHints = self::BEFORE_RUNNING_HINTS;
        $this
            ->setName('ibexa:roles:cleanup-limitation-values')
            ->setDescription('Reports Policy Limitation values which no longer resolve to an existing entity, and optionally removes them.')
            ->addOption(
                'fix',
                null,
                InputOption::VALUE_NONE,
                'Remove the reported Limitation values. A Policy left without any Limitation value is removed as well.'
            )
            ->addOption(
                'force',
                null,
                InputOption::VALUE_NONE,
                'Prevents the confirmation question asked by --fix. Please use it carefully.'
            )
            ->addOption(
                'user',
                'u',
                InputOption::VALUE_OPTIONAL,
                'Ibexa username (with a Role containing at least role policies: read, update)',
                self::DEFAULT_REPOSITORY_USER
            )
            ->setHelp(
                <<<EOT
The command <info>%command.name%</info> finds Policy Limitation values which name an entity that no
longer exists, for instance a Content Type Limitation left behind by ContentTypeService::deleteContentType().
Such values never grant anything, but they make RoleService::copyRole() reject the Role.

Without <info>--fix</info> the command only reports what it finds.

A Policy whose Limitation would be left without any value is removed rather than emptied. Saving it
empty drops the Limitation from the Policy and leaves the Policy granted on everything, while
removing the Policy changes nothing: a Limitation whose every value is dangling already grants nothing.

A Limitation holding lists of values under keys, such as UserPermissions, is only reported and never
changed when one of those lists would be left empty as that would be treated as "no restriction on that dimension".

SiteAccess Limitation values are stored as an unsigned CRC32 of the SiteAccess name, so the report shows
a number rather than a name.

{$beforeRunningHints}
EOT
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // We don't load repo services before execute() to avoid loading before SiteAccess is set.
        $roleService = $this->repository->getRoleService();
        $userService = $this->repository->getUserService();
        $permissionResolver = $this->repository->getPermissionResolver();

        $permissionResolver->setCurrentUserReference(
            $userService->loadUserByLogin($input->getOption('user'))
        );

        $findings = [];
        $skipped = [];
        foreach ($roleService->loadRoles() as $role) {
            foreach ($role->getPolicies() as $policy) {
                foreach ($policy->getLimitations() as $limitation) {
                    $skippedAt = sprintf(
                        'Limitation "%s" on Policy %s/%s (%d) of Role "%s" (%d)',
                        $limitation->getIdentifier(),
                        $policy->module,
                        $policy->function,
                        $policy->id,
                        $role->identifier,
                        $role->id
                    );

                    try {
                        $danglingValues = $this->findDanglingValues($limitation);
                    } catch (LimitationNotFoundException $e) {
                        $skipped[$skippedAt] = 'no Limitation type is configured for it';

                        continue;
                    } catch (Throwable $e) {
                        $skipped[$skippedAt] = $e->getMessage();

                        continue;
                    }

                    if ($danglingValues !== []) {
                        $findings[] = new DanglingLimitationValues($role, $policy, $limitation, $danglingValues);
                    }
                }
            }
        }

        foreach ($skipped as $where => $reason) {
            $output->writeln(sprintf('<comment>%s was skipped: %s</comment>', $where, $reason));
        }

        if ($findings === []) {
            $output->writeln('<info>No dangling Policy Limitation values found.</info>');

            return 0;
        }

        $this->renderFindings($output, $findings);

        if (!$input->getOption('fix')) {
            $output->writeln('');
            $output->writeln('<comment>Re-run with --fix to remove the values listed above.</comment>');

            return 0;
        }

        $output->writeln('');
        $output->writeln(self::BEFORE_RUNNING_HINTS);
        $output->writeln('');

        // Non-interactive runs get the default answer, so scripted use has to pass --force
        $io = new SymfonyStyle($input, $output);
        if (!$input->getOption('force')
            && !$io->confirm('Remove the Limitation values listed above?', false)
        ) {
            $output->writeln('<comment>Nothing was changed. Use --force to skip this question.</comment>');

            return 0;
        }

        return $this->fix($output, $findings);
    }

    /**
     * @param \Ibexa\Bundle\Core\Command\Role\DanglingLimitationValues[] $findings
     */
    private function renderFindings(OutputInterface $output, array $findings): void
    {
        // A Policy is removed when any one of its Limitations is left without values, so the flag
        // has to be decided per Policy rather than per finding.
        $removesPolicy = [];
        foreach ($findings as $finding) {
            $policyId = $finding->getPolicy()->id;
            $removesPolicy[$policyId] = ($removesPolicy[$policyId] ?? false) || $finding->emptiesLimitation();
        }

        $table = new Table($output);
        $table->setHeaders(['Role', 'Policy', 'Limitation', 'Dangling values', 'Policy removed', '--fix']);
        foreach ($findings as $finding) {
            $role = $finding->getRole();
            $policy = $finding->getPolicy();

            $table->addRow([
                sprintf('%s (%d)', $role->identifier, $role->id),
                sprintf('%s/%s (%d)', $policy->module, $policy->function, $policy->id),
                $finding->getLimitation()->getIdentifier(),
                implode(', ', $finding->getLabels()),
                $removesPolicy[$policy->id] ? 'yes' : 'no',
                $finding->cannotPreserveGrants() ? 'by hand' : 'yes',
            ]);
        }
        $table->render();
    }

    /**
     * @param \Ibexa\Bundle\Core\Command\Role\DanglingLimitationValues[] $findings
     */
    private function fix(OutputInterface $output, array $findings): int
    {
        $roleService = $this->repository->getRoleService();

        $findingsByRoleId = [];
        foreach ($findings as $finding) {
            $findingsByRoleId[$finding->getRole()->id][$finding->getPolicy()->id][] = $finding;
        }

        $failed = false;
        foreach ($findingsByRoleId as $roleId => $findingsByPolicyId) {
            $role = $roleService->loadRole($roleId);

            $unrepairable = $this->findUnrepairable($findingsByPolicyId);
            if ($unrepairable !== []) {
                foreach ($unrepairable as $finding) {
                    $output->writeln(sprintf(
                        '<comment>Limitation "%s" on Policy %d of Role "%s" (%d) has to be fixed by hand:'
                        . ' removing %s would leave one of its value lists empty, which grants it on everything.</comment>',
                        $finding->getLimitation()->getIdentifier(),
                        $finding->getPolicy()->id,
                        $role->identifier,
                        $roleId,
                        implode(', ', $finding->getLabels())
                    ));
                }
                $failed = true;

                continue;
            }

            try {
                $roleService->loadRoleDraftByRoleId($roleId);

                $output->writeln(sprintf(
                    '<comment>Role "%s" (%d) already has a draft and was skipped. Publish or delete that draft, then re-run.</comment>',
                    $role->identifier,
                    $roleId
                ));
                $failed = true;

                continue;
            } catch (NotFoundException $e) {
                // no draft in the way, one can be created below
            }

            $roleDraft = $roleService->createRoleDraft($role);

            try {
                $this->applyToDraft($roleDraft, $findingsByPolicyId, $role, $roleId);
            } catch (Throwable $e) {
                // The Role keeps its Policies as they were, but the draft would block a re-run
                $roleService->deleteRoleDraft($roleDraft);

                $output->writeln(sprintf(
                    '<error>Role "%s" (%d) was left unchanged: %s</error>',
                    $role->identifier,
                    $roleId,
                    $e->getMessage()
                ));
                $failed = true;

                continue;
            }

            $output->writeln(sprintf('<info>Role "%s" (%d) updated.</info>', $role->identifier, $roleId));
        }

        return $failed ? 1 : 0;
    }

    /**
     * @param array<int, \Ibexa\Bundle\Core\Command\Role\DanglingLimitationValues[]> $findingsByPolicyId
     *
     * @return \Ibexa\Bundle\Core\Command\Role\DanglingLimitationValues[]
     */
    private function findUnrepairable(array $findingsByPolicyId): array
    {
        $unrepairable = [];
        foreach ($findingsByPolicyId as $findings) {
            foreach ($findings as $finding) {
                if ($finding->cannotPreserveGrants()) {
                    $unrepairable[] = $finding;
                }
            }
        }

        return $unrepairable;
    }

    /**
     * @param array<int, \Ibexa\Bundle\Core\Command\Role\DanglingLimitationValues[]> $findingsByPolicyId
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\BadStateException
     */
    private function applyToDraft(RoleDraft $roleDraft, array $findingsByPolicyId, Role $role, int $roleId): void
    {
        $roleService = $this->repository->getRoleService();

        foreach ($roleDraft->getPolicies() as $policyDraft) {
            if (!$policyDraft instanceof PolicyDraft) {
                // Shouldn't happen but check is needed as $policyDraft->originalId is used below
                throw new BadStateException(
                    '$roleDraft',
                    sprintf(
                        'Policy %d of Role "%s" (%d) is not a draft Policy',
                        $policyDraft->id,
                        $role->identifier,
                        $roleId
                    )
                );
            }

            if (!isset($findingsByPolicyId[$policyDraft->originalId])) {
                continue;
            }

            $limitations = $this->pruneLimitations($policyDraft, $findingsByPolicyId[$policyDraft->originalId]);

            if ($limitations === []) {
                $roleDraft = $roleService->removePolicyByRoleDraft($roleDraft, $policyDraft);

                continue;
            }

            $policyUpdateStruct = $roleService->newPolicyUpdateStruct();
            foreach ($limitations as $limitation) {
                $policyUpdateStruct->addLimitation($limitation);
            }
            $roleService->updatePolicyByRoleDraft($roleDraft, $policyDraft, $policyUpdateStruct);
        }

        $roleService->publishRoleDraft($roleDraft);
    }

    /**
     * @param \Ibexa\Bundle\Core\Command\Role\DanglingLimitationValues[] $findings
     *
     * @return \Ibexa\Contracts\Core\Repository\Values\User\Limitation[]
     */
    private function pruneLimitations(Policy $policyDraft, array $findings): array
    {
        $prunedByIdentifier = [];
        foreach ($findings as $finding) {
            $prunedByIdentifier[$finding->getLimitation()->getIdentifier()] = $finding->getPrunedValues();
        }

        $limitations = [];
        foreach ($policyDraft->getLimitations() as $limitation) {
            $values = $prunedByIdentifier[$limitation->getIdentifier()] ?? $limitation->limitationValues;

            // Safe because the Limitation grants nothing as it stands: either a plain list of
            // dangling values, or lists under keys that are all dangling. findUnrepairable() in
            // fix() has already turned away the maps where emptying one list would mean
            // "unrestricted".
            if (DanglingLimitationValues::holdsNoValues($values)) {
                return [];
            }

            $limitations[] = $this->withValues($limitation, $values);
        }

        return $limitations;
    }

    /**
     * @return array<array{key: string|null, value: mixed}>
     *
     * @throws \Ibexa\Core\Base\Exceptions\NotFound\LimitationNotFoundException if the Limitation type is not configured
     * @throws \Ibexa\Core\Base\Exceptions\InvalidArgumentType if the values are in a shape this command cannot
     *         take apart one value at a time
     */
    private function findDanglingValues(Limitation $limitation): array
    {
        $limitationType = $this->repository->getRoleService()->getLimitationType($limitation->getIdentifier());
        $limitationValues = $limitation->limitationValues;

        $danglingValues = [];

        if ($this->isListOfValues($limitationValues)) {
            foreach ($limitationValues as $value) {
                if ($this->namesMissingEntity($limitationType, $this->withValues($limitation, [$value]))) {
                    $danglingValues[] = ['key' => null, 'value' => $value];
                }
            }

            return $danglingValues;
        }

        // Some Limitations hold a map of lists rather than a plain list — UserPermissions keeps its
        // values under "roles" and "user_groups". Isolating one value means rebuilding the whole
        // map, because acceptValue() requires every key to be present.
        if (!$this->isMapOfLists($limitationValues)) {
            throw new InvalidArgumentType('$limitation->limitationValues', 'list|array<list>', $limitationValues);
        }

        $emptied = array_map(
            static function ($values) {
                return is_array($values) ? [] : $values;
            },
            $limitationValues
        );

        foreach ($limitationValues as $key => $values) {
            if (!is_array($values)) {
                continue;
            }

            foreach ($values as $value) {
                $isolatedValues = $emptied;
                $isolatedValues[$key] = [$value];

                if ($this->namesMissingEntity($limitationType, $this->withValues($limitation, $isolatedValues))) {
                    $danglingValues[] = ['key' => (string)$key, 'value' => $value];
                }
            }
        }

        return $danglingValues;
    }

    /**
     * Limitation types normalize their values in acceptValue() and require it to run before
     * validate(). Both mutate the value object, so callers get a copy to hand over.
     *
     * @param array<mixed> $limitationValues
     */
    private function withValues(Limitation $limitation, array $limitationValues): Limitation
    {
        $copy = clone $limitation;
        $copy->limitationValues = $limitationValues;

        return $copy;
    }

    /**
     * Checked one value at a time, so the offending one is known without reading it back out of the
     * error message, where the %value% placeholder is not substituted cleanly.
     */
    private function namesMissingEntity(Type $limitationType, Limitation $limitation): bool
    {
        $limitationType->acceptValue($limitation);

        foreach ($limitationType->validate($limitation) as $validationError) {
            $message = $validationError->getTranslatableMessage();
            if ($message instanceof Message && $this->isMissingEntityError($message)) {
                return true;
            }
        }

        return false;
    }

    private function isMissingEntityError(Message $message): bool
    {
        $renderedMessage = (string)$message;

        if (substr($renderedMessage, -strlen(self::MISSING_ENTITY_SUFFIX)) === self::MISSING_ENTITY_SUFFIX) {
            return true;
        }

        foreach (self::MISSING_ENTITY_MESSAGES as $missingEntityMessage) {
            if (strpos($renderedMessage, $missingEntityMessage) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Substitute for array_is_list(), which is only available in PHP 8.1 or a polyfill this package
     * doesn't require.
     *
     * @param array<mixed> $limitationValues
     */
    private function isListOfValues(array $limitationValues): bool
    {
        return array_values($limitationValues) === $limitationValues;
    }

    /**
     * @param array<mixed> $limitationValues
     */
    private function isMapOfLists(array $limitationValues): bool
    {
        if ($limitationValues === []) {
            return false;
        }

        foreach ($limitationValues as $values) {
            if ($values === null) {
                continue;
            }

            if (!is_array($values) || array_values($values) !== $values) {
                return false;
            }
        }

        return true;
    }
}
