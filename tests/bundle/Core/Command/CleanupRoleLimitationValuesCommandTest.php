<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Bundle\Core\Command;

use Ibexa\Bundle\Core\Command\CleanupRoleLimitationValuesCommand;
use Ibexa\Contracts\Core\Limitation\Type;
use Ibexa\Contracts\Core\Repository\PermissionResolver;
use Ibexa\Contracts\Core\Repository\Repository;
use Ibexa\Contracts\Core\Repository\RoleService;
use Ibexa\Contracts\Core\Repository\UserService;
use Ibexa\Contracts\Core\Repository\Values\User\Limitation;
use Ibexa\Contracts\Core\Repository\Values\User\Limitation\ContentTypeLimitation;
use Ibexa\Contracts\Core\Repository\Values\User\RoleDraft;
use Ibexa\Contracts\Core\Repository\Values\User\User;
use Ibexa\Core\Base\Exceptions\InvalidArgumentException;
use Ibexa\Core\Base\Exceptions\NotFoundException;
use Ibexa\Core\FieldType\ValidationError;
use Ibexa\Core\Repository\Values\User\Policy;
use Ibexa\Core\Repository\Values\User\PolicyDraft as PolicyDraftStub;
use Ibexa\Core\Repository\Values\User\PolicyUpdateStruct;
use Ibexa\Core\Repository\Values\User\Role;
use Ibexa\Core\Repository\Values\User\RoleDraft as RoleDraftStub;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @covers \Ibexa\Bundle\Core\Command\CleanupRoleLimitationValuesCommand
 */
final class CleanupRoleLimitationValuesCommandTest extends TestCase
{
    private const MISSING_ENTITY_MESSAGE = "limitationValues[%key%] => '%value%' does not exist in the backend";

    /** @var \Ibexa\Contracts\Core\Repository\RoleService&\PHPUnit\Framework\MockObject\MockObject */
    private $roleService;

    protected function setUp(): void
    {
        $this->roleService = $this->createMock(RoleService::class);
    }

    public function testReportsDanglingValueWithoutChangingAnything(): void
    {
        $this->givenRoles([$this->buildRole(99, 'DeletedLocationRole', 859, new ContentTypeLimitation(['limitationValues' => ['858']]))]);
        $this->givenLimitationTypeRejecting(['858']);

        $this->roleService->expects(self::never())->method('createRoleDraft');
        $this->roleService->expects(self::never())->method('publishRoleDraft');

        $tester = $this->execute([]);

        self::assertSame(0, $tester->getStatusCode());
        self::assertStringContainsString('DeletedLocationRole (99)', $tester->getDisplay());
        self::assertStringContainsString('858', $tester->getDisplay());
        self::assertStringContainsString('Re-run with --fix', $tester->getDisplay());
    }

    public function testFixRefusesWhenNoRepairPreservesGrants(): void
    {
        // Emptying "roles" would make UserPermissionsLimitationType::evaluate() grant every Role,
        // and storage cannot tell an emptied value list from one that was never restricted.
        $limitation = new ContentTypeLimitation(['limitationValues' => ['roles' => [15], 'user_groups' => [11]]]);
        $this->givenRoles([$this->buildRole(14, 'Company admin', 401, $limitation)]);
        $this->givenLimitationTypeRejecting([15]);

        $this->roleService->expects(self::never())->method('createRoleDraft');
        $this->roleService->expects(self::never())->method('publishRoleDraft');
        $this->roleService->expects(self::never())->method('updatePolicyByRoleDraft');
        $this->roleService->expects(self::never())->method('removePolicyByRoleDraft');

        $tester = $this->execute(['--fix' => true, '--force' => true]);

        self::assertSame(1, $tester->getStatusCode());
        self::assertStringContainsString('has to be fixed by hand', $tester->getDisplay());
    }

    public function testFixPrunesOnlyTheDanglingValue(): void
    {
        $limitation = new ContentTypeLimitation(['limitationValues' => ['4', '8']]);
        $role = $this->buildRole(99, 'probe_role', 859, $limitation);
        $this->givenRoles([$role]);
        $this->givenLimitationTypeRejecting(['8']);
        $this->givenDraftFor($role, 859, $limitation);

        $policyUpdateStruct = new PolicyUpdateStruct();
        $this->roleService->method('newPolicyUpdateStruct')->willReturn($policyUpdateStruct);

        $this->roleService->expects(self::never())->method('removePolicyByRoleDraft');
        $this->roleService->expects(self::once())->method('updatePolicyByRoleDraft');
        $this->roleService->expects(self::once())->method('publishRoleDraft');

        $tester = $this->execute(['--fix' => true, '--force' => true]);

        self::assertSame(0, $tester->getStatusCode());
        self::assertStringContainsString('updated', $tester->getDisplay());

        // addLimitation() keys by Limitation identifier
        $updated = iterator_to_array($this->asIterator($policyUpdateStruct->getLimitations()));
        self::assertCount(1, $updated);
        self::assertArrayHasKey(Limitation::CONTENTTYPE, $updated);
        self::assertSame(['4'], $updated[Limitation::CONTENTTYPE]->limitationValues);
    }

    public function testFixRemovesAPolicyWhoseEveryValueIsDangling(): void
    {
        $limitation = new ContentTypeLimitation(['limitationValues' => ['8']]);
        $role = $this->buildRole(99, 'probe_role', 859, $limitation);
        $this->givenRoles([$role]);
        $this->givenLimitationTypeRejecting(['8']);
        $this->givenDraftFor($role, 859, $limitation);

        $this->roleService->expects(self::never())->method('updatePolicyByRoleDraft');
        $this->roleService->expects(self::once())->method('removePolicyByRoleDraft');
        $this->roleService->expects(self::once())->method('publishRoleDraft');

        self::assertSame(0, $this->execute(['--fix' => true, '--force' => true])->getStatusCode());
    }

    public function testFixSkipsARoleThatAlreadyHasADraft(): void
    {
        $limitation = new ContentTypeLimitation(['limitationValues' => ['8']]);
        $this->givenRoles([$this->buildRole(99, 'probe_role', 859, $limitation)]);
        $this->givenLimitationTypeRejecting(['8']);
        $this->roleService->method('loadRole')->willReturn($this->buildRole(99, 'probe_role', 859, $limitation));
        $this->roleService->method('loadRoleDraftByRoleId')->willReturn($this->createMock(RoleDraft::class));

        $this->roleService->expects(self::never())->method('createRoleDraft');
        $this->roleService->expects(self::never())->method('publishRoleDraft');

        $tester = $this->execute(['--fix' => true, '--force' => true]);

        self::assertSame(1, $tester->getStatusCode());
        self::assertStringContainsString('already has a draft', $tester->getDisplay());
    }

    public function testFixDiscardsTheDraftWhenApplyingFails(): void
    {
        $limitation = new ContentTypeLimitation(['limitationValues' => ['4', '8']]);
        $role = $this->buildRole(99, 'probe_role', 859, $limitation);
        $this->givenRoles([$role]);
        $this->givenLimitationTypeRejecting(['8']);
        $this->givenDraftFor($role, 859, $limitation);

        $this->roleService->method('newPolicyUpdateStruct')->willReturn(new PolicyUpdateStruct());
        $this->roleService->method('updatePolicyByRoleDraft')
            ->willThrowException(new InvalidArgumentException('policy', 'Limitation is not applicable'));

        $this->roleService->expects(self::once())->method('deleteRoleDraft');
        $this->roleService->expects(self::never())->method('publishRoleDraft');

        $tester = $this->execute(['--fix' => true, '--force' => true]);

        self::assertSame(1, $tester->getStatusCode());
        self::assertStringContainsString('was left unchanged', $tester->getDisplay());
    }

    public function testFixWithoutForceChangesNothingWhenNotConfirmed(): void
    {
        $this->givenRoles([$this->buildRole(99, 'DeletedLocationRole', 859, new ContentTypeLimitation(['limitationValues' => ['858']]))]);
        $this->givenLimitationTypeRejecting(['858']);

        $this->roleService->expects(self::never())->method('createRoleDraft');

        $tester = $this->execute(['--fix' => true], ['no']);

        self::assertSame(0, $tester->getStatusCode());
        self::assertStringContainsString('Nothing was changed', $tester->getDisplay());
    }

    public function testReportsNothingWhenEveryValueResolves(): void
    {
        $this->givenRoles([$this->buildRole(99, 'Anonymous', 859, new ContentTypeLimitation(['limitationValues' => ['1']]))]);
        $this->givenLimitationTypeRejecting([]);

        $tester = $this->execute([]);

        self::assertSame(0, $tester->getStatusCode());
        self::assertStringContainsString('No dangling Policy Limitation values found', $tester->getDisplay());
    }

    private function givenDraftFor(Role $role, int $policyId, Limitation $limitation): void
    {
        // RoleDraft routes every property to its inner Role, so the draft Policies go in there
        $roleDraft = new RoleDraftStub([
            'innerRole' => new Role([
                'id' => $role->id,
                'identifier' => $role->identifier,
                'policies' => [
                    new PolicyDraftStub([
                        'innerPolicy' => new Policy([
                            'id' => $policyId + 1000,
                            'roleId' => $role->id,
                            'module' => 'content',
                            'function' => 'read',
                            'limitations' => [$limitation],
                        ]),
                        'originalId' => $policyId,
                    ]),
                ],
            ]),
        ]);

        $this->roleService->method('loadRole')->willReturn($role);
        $this->roleService->method('loadRoleDraftByRoleId')
            ->willThrowException(new NotFoundException('RoleDraft', $role->id));
        $this->roleService->method('createRoleDraft')->willReturn($roleDraft);
        $this->roleService->method('removePolicyByRoleDraft')->willReturn($roleDraft);
    }

    /**
     * @param iterable<string, \Ibexa\Contracts\Core\Repository\Values\User\Limitation> $limitations
     *
     * @return \Generator<string, \Ibexa\Contracts\Core\Repository\Values\User\Limitation>
     */
    private function asIterator(iterable $limitations): iterable
    {
        yield from $limitations;
    }

    /**
     * @param \Ibexa\Contracts\Core\Repository\Values\User\Role[] $roles
     */
    private function givenRoles(array $roles): void
    {
        $this->roleService->method('loadRoles')->willReturn($roles);
    }

    /**
     * Every value in $rejected is reported as naming an entity that no longer exists.
     *
     * @param array<mixed> $rejected
     */
    private function givenLimitationTypeRejecting(array $rejected): void
    {
        $limitationType = $this->createMock(Type::class);
        $limitationType->method('validate')->willReturnCallback(
            static function (Limitation $limitation) use ($rejected): array {
                $values = $limitation->limitationValues;
                foreach (is_array($values) ? $values : [] as $value) {
                    $isolated = is_array($value) ? $value : [$value];
                    foreach ($isolated as $candidate) {
                        if (in_array($candidate, $rejected, false)) {
                            return [new ValidationError(self::MISSING_ENTITY_MESSAGE, null, ['value' => $candidate, 'key' => 0])];
                        }
                    }
                }

                return [];
            }
        );

        $this->roleService->method('getLimitationType')->willReturn($limitationType);
    }

    private function buildRole(int $roleId, string $identifier, int $policyId, Limitation $limitation): Role
    {
        return new Role([
            'id' => $roleId,
            'identifier' => $identifier,
            'policies' => [
                new Policy([
                    'id' => $policyId,
                    'roleId' => $roleId,
                    'module' => 'content',
                    'function' => 'read',
                    'limitations' => [$limitation],
                ]),
            ],
        ]);
    }

    /**
     * @param array<string, bool|string> $input
     * @param string[] $answers
     */
    private function execute(array $input, array $answers = []): CommandTester
    {
        $tester = new CommandTester(new CleanupRoleLimitationValuesCommand($this->buildRepository()));
        if ($answers !== []) {
            $tester->setInputs($answers);
        }
        $tester->execute($input);

        return $tester;
    }

    /**
     * @return \Ibexa\Contracts\Core\Repository\Repository&\PHPUnit\Framework\MockObject\MockObject
     */
    private function buildRepository(): MockObject
    {
        $userService = $this->createMock(UserService::class);
        $userService->method('loadUserByLogin')->willReturn($this->createMock(User::class));

        $repository = $this->createMock(Repository::class);
        $repository->method('getRoleService')->willReturn($this->roleService);
        $repository->method('getUserService')->willReturn($userService);
        $repository->method('getPermissionResolver')->willReturn($this->createMock(PermissionResolver::class));

        return $repository;
    }
}
