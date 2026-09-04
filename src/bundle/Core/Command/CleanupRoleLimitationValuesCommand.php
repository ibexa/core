<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Bundle\Core\Command;

use Ibexa\Bundle\Core\Command\Role\DanglingLimitationValues;
use Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException;
use Ibexa\Contracts\Core\Repository\Repository;
use Ibexa\Contracts\Core\Repository\Values\Translation\Message;
use Ibexa\Contracts\Core\Repository\Values\User\Limitation;
use Ibexa\Contracts\Core\Repository\Values\User\Policy;
use Ibexa\Contracts\Core\Repository\Values\User\PolicyDraft;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
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
     * Every Limitation type reporting a value whose entity is gone ends its message this way. A value
     * rejected for any other reason is malformed rather than dangling, and must not be removed.
     */
    private const MISSING_ENTITY_SUFFIX = 'does not exist in the backend';

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

A Policy whose Limitation would be left without any value is removed instead of being emptied: a Policy
carrying no Limitation is granted on every entity, so emptying it would widen the permission.

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
                    if (!$this->hasPlainListOfValues($limitation)) {
                        $skipped[$limitation->getIdentifier()] = 'its values are not a plain list and have to be reviewed by hand';

                        continue;
                    }

                    try {
                        $danglingValues = $this->findDanglingValues($limitation);
                    } catch (NotFoundException $e) {
                        $skipped[$limitation->getIdentifier()] = 'no Limitation type is configured for it';

                        continue;
                    } catch (Throwable $e) {
                        $skipped[$limitation->getIdentifier()] = $e->getMessage();

                        continue;
                    }

                    if ($danglingValues !== []) {
                        $findings[] = new DanglingLimitationValues($role, $policy, $limitation, $danglingValues);
                    }
                }
            }
        }

        foreach ($skipped as $identifier => $reason) {
            $output->writeln(sprintf(
                '<comment>Limitation "%s" was skipped: %s</comment>',
                $identifier,
                $reason
            ));
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

        return $this->fix($output, $findings);
    }

    /**
     * @param \Ibexa\Bundle\Core\Command\Role\DanglingLimitationValues[] $findings
     */
    private function renderFindings(OutputInterface $output, array $findings): void
    {
        $table = new Table($output);
        $table->setHeaders(['Role', 'Policy', 'Limitation', 'Dangling values', 'Policy removed']);
        foreach ($findings as $finding) {
            $role = $finding->getRole();
            $policy = $finding->getPolicy();

            $table->addRow([
                sprintf('%s (%d)', $role->identifier, $role->id),
                sprintf('%s/%s (%d)', $policy->module, $policy->function, $policy->id),
                $finding->getLimitation()->getIdentifier(),
                implode(', ', $finding->getDanglingValues()),
                $finding->emptiesLimitation() ? 'yes' : 'no',
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
            foreach ($roleDraft->getPolicies() as $policyDraft) {
                if (!$policyDraft instanceof PolicyDraft
                    || !isset($findingsByPolicyId[$policyDraft->originalId])
                ) {
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

            $output->writeln(sprintf('<info>Role "%s" (%d) updated.</info>', $role->identifier, $roleId));
        }

        return $failed ? 1 : 0;
    }

    /**
     * @param \Ibexa\Bundle\Core\Command\Role\DanglingLimitationValues[] $findings
     *
     * @return \Ibexa\Contracts\Core\Repository\Values\User\Limitation[]
     */
    private function pruneLimitations(Policy $policyDraft, array $findings): array
    {
        $danglingValuesByIdentifier = [];
        foreach ($findings as $finding) {
            $danglingValuesByIdentifier[$finding->getLimitation()->getIdentifier()] = $finding->getDanglingValues();
        }

        $limitations = [];
        foreach ($policyDraft->getLimitations() as $limitation) {
            $keptValues = array_values(
                array_diff($limitation->limitationValues, $danglingValuesByIdentifier[$limitation->getIdentifier()] ?? [])
            );

            if ($keptValues === []) {
                return [];
            }

            $prunedLimitation = clone $limitation;
            $prunedLimitation->limitationValues = $keptValues;
            $limitations[] = $prunedLimitation;
        }

        return $limitations;
    }

    /**
     * @return string[]
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException if the Limitation type is not configured
     */
    private function findDanglingValues(Limitation $limitation): array
    {
        $limitationType = $this->repository->getRoleService()->getLimitationType($limitation->getIdentifier());

        $danglingValues = [];
        foreach ($limitation->limitationValues as $limitationValue) {
            // Validated one value at a time, so the offending one is known without reading it back out of
            // the error message, where the %value% placeholder is not substituted cleanly
            $probe = clone $limitation;
            $probe->limitationValues = [$limitationValue];

            // Limitation types normalise their values in acceptValue() and require it to run first.
            // It mutates the value object, which is what the clone above is for.
            $limitationType->acceptValue($probe);

            foreach ($limitationType->validate($probe) as $validationError) {
                $message = $validationError->getTranslatableMessage();
                if ($message instanceof Message && $this->reportsMissingEntity($message)) {
                    $danglingValues[] = (string)$limitationValue;

                    break;
                }
            }
        }

        return $danglingValues;
    }

    private function reportsMissingEntity(Message $message): bool
    {
        $renderedMessage = (string)$message;

        return substr($renderedMessage, -strlen(self::MISSING_ENTITY_SUFFIX)) === self::MISSING_ENTITY_SUFFIX;
    }

    private function hasPlainListOfValues(Limitation $limitation): bool
    {
        $limitationValues = $limitation->limitationValues;

        return array_values($limitationValues) === $limitationValues;
    }
}
