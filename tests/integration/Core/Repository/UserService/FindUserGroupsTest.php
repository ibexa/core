<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Integration\Core\Repository\UserService;

use Closure;
use Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException;
use Ibexa\Contracts\Core\Repository\Values\User\Query\Criterion\UserGroup\LogicalAnd;
use Ibexa\Contracts\Core\Repository\Values\User\Query\Criterion\UserGroup\ParentUserGroupId;
use Ibexa\Contracts\Core\Repository\Values\User\Query\Criterion\UserGroup\UserId;
use Ibexa\Contracts\Core\Repository\Values\User\Query\SortClause;
use Ibexa\Contracts\Core\Repository\Values\User\Query\SortClause\Id;
use Ibexa\Contracts\Core\Repository\Values\User\Query\UserGroupCriterionInterface;
use Ibexa\Contracts\Core\Repository\Values\User\Query\UserGroupQuery;
use Ibexa\Contracts\Core\Repository\Values\User\UserGroup;
use Ibexa\Contracts\Core\Repository\Values\User\UserGroupList;

/**
 * @covers \Ibexa\Contracts\Core\Repository\UserService::findUserGroups
 */
final class FindUserGroupsTest extends AbstractFindTestCase
{
    private const FIXTURE_USER_GROUP_IDS = [self::MAIN_USER_GROUP_ID, 11, self::ADMINISTRATOR_USERS_GROUP_ID, 13, 42, 59];
    private const CREATED_USER_GROUPS = ['groupA', 'groupA1', 'groupA2', 'groupB'];

    public function testFindUserGroupsWithoutQueryReturnsAllUserGroupsSortedById(): void
    {
        $userGroupList = $this->getIbexaTestCore()->getUserService()->findUserGroups();

        $expectedIds = [...self::FIXTURE_USER_GROUP_IDS, ...$this->getIds(self::CREATED_USER_GROUPS)];
        sort($expectedIds);

        self::assertSame(count($expectedIds), $userGroupList->getTotalCount());
        self::assertSame($expectedIds, $this->extractIds($userGroupList));
        self::assertContainsOnlyInstancesOf(UserGroup::class, $userGroupList);
    }

    /**
     * @param \Closure(array<string, int>): \Ibexa\Contracts\Core\Repository\Values\User\Query\UserGroupCriterionInterface $criterionFactory
     * @param list<string> $expectedAliases
     *
     * @dataProvider provideForTestFindUserGroupsByCriterion
     */
    public function testFindUserGroupsByCriterion(Closure $criterionFactory, array $expectedAliases): void
    {
        $userGroupList = $this->getIbexaTestCore()->getUserService()->findUserGroups(
            new UserGroupQuery($criterionFactory($this->ids))
        );

        $expectedIds = $this->getIds($expectedAliases);
        sort($expectedIds);

        self::assertSame($expectedIds, $this->extractIds($userGroupList));
        self::assertSame(count($expectedIds), $userGroupList->getTotalCount());
    }

    /**
     * @return iterable<string, array{\Closure(array<string, int>): \Ibexa\Contracts\Core\Repository\Values\User\Query\UserGroupCriterionInterface, list<string>}>
     */
    public static function provideForTestFindUserGroupsByCriterion(): iterable
    {
        yield 'user id of a user assigned to a single group' => [
            static fn (array $ids): UserGroupCriterionInterface => new UserId($ids['erin']),
            ['groupA1'],
        ];

        yield 'user id of a user assigned to multiple groups' => [
            static fn (array $ids): UserGroupCriterionInterface => new UserId($ids['bob']),
            ['groupA', 'groupB'],
        ];

        yield 'parent user group id' => [
            static fn (array $ids): UserGroupCriterionInterface => new ParentUserGroupId($ids['groupA']),
            ['groupA1', 'groupA2'],
        ];

        yield 'parent user group id with no sub-groups' => [
            static fn (array $ids): UserGroupCriterionInterface => new ParentUserGroupId($ids['groupB']),
            [],
        ];

        yield 'logical and of parent user group id and user id' => [
            static fn (array $ids): UserGroupCriterionInterface => new LogicalAnd([
                new ParentUserGroupId($ids['groupA']),
                new UserId($ids['erin']),
            ]),
            ['groupA1'],
        ];

        yield 'nested logical and' => [
            static fn (array $ids): UserGroupCriterionInterface => new LogicalAnd([
                new LogicalAnd([]),
                new LogicalAnd([new ParentUserGroupId(self::MAIN_USER_GROUP_ID)]),
                new UserId($ids['bob']),
            ]),
            ['groupA', 'groupB'],
        ];

        yield 'non-existent user id' => [
            static fn (): UserGroupCriterionInterface => new UserId(self::NON_EXISTENT_ID),
            [],
        ];

        yield 'user id pointing to a user group' => [
            static fn (array $ids): UserGroupCriterionInterface => new UserId($ids['groupA1']),
            [],
        ];

        yield 'non-existent parent user group id' => [
            static fn (): UserGroupCriterionInterface => new ParentUserGroupId(self::NON_EXISTENT_ID),
            [],
        ];

        yield 'parent user group id pointing to a user' => [
            static fn (array $ids): UserGroupCriterionInterface => new ParentUserGroupId($ids['alice']),
            [],
        ];
    }

    public function testFindUserGroupsOfFixtureUser(): void
    {
        $userGroupList = $this->getIbexaTestCore()->getUserService()->findUserGroups(
            new UserGroupQuery(new UserId(self::ADMIN_USER_ID))
        );

        self::assertSame([self::ADMINISTRATOR_USERS_GROUP_ID], $this->extractIds($userGroupList));
    }

    /**
     * @param list<string> $expectedAliases
     *
     * @dataProvider provideForTestPagination
     */
    public function testPagination(int $offset, ?int $limit, array $expectedAliases): void
    {
        $userGroupList = $this->getIbexaTestCore()->getUserService()->findUserGroups(
            new UserGroupQuery(new UserId($this->ids['bob']), [], $offset, $limit)
        );

        self::assertSame($this->getIds($expectedAliases), $this->extractIds($userGroupList));
        self::assertSame(2, $userGroupList->getTotalCount());
    }

    /**
     * @return iterable<string, array{int, ?int, list<string>}>
     */
    public static function provideForTestPagination(): iterable
    {
        yield 'first page' => [0, 1, ['groupA']];
        yield 'last page' => [1, 1, ['groupB']];
        yield 'offset past the end' => [5, 1, []];
        yield 'no limit' => [0, null, ['groupA', 'groupB']];
        yield 'count only' => [0, 0, []];
    }

    public function testPaginationWithParentUserGroupCriterion(): void
    {
        $userService = $this->getIbexaTestCore()->getUserService();
        $criterion = new ParentUserGroupId(self::MAIN_USER_GROUP_ID);

        $allIds = $this->extractIds($userService->findUserGroups(new UserGroupQuery($criterion, [], 0, null)));
        $firstPage = $userService->findUserGroups(new UserGroupQuery($criterion, [], 0, 4));
        $secondPage = $userService->findUserGroups(new UserGroupQuery($criterion, [], 4, 4));

        // Members, Administrator users, Editors, Anonymous users, Partners, groupA, groupB
        self::assertCount(7, $allIds);
        self::assertSame(7, $firstPage->getTotalCount());
        self::assertSame(7, $secondPage->getTotalCount());
        self::assertSame($allIds, [...$this->extractIds($firstPage), ...$this->extractIds($secondPage)]);
    }

    /**
     * @testWith ["ascending"]
     *           ["descending"]
     */
    public function testSortById(string $direction): void
    {
        $userGroupList = $this->getIbexaTestCore()->getUserService()->findUserGroups(
            new UserGroupQuery(new ParentUserGroupId(self::MAIN_USER_GROUP_ID), [new Id($direction)], 0, null)
        );

        $expectedIds = [11, self::ADMINISTRATOR_USERS_GROUP_ID, 13, 42, 59, ...$this->getIds(['groupA', 'groupB'])];
        sort($expectedIds);
        if ($direction === SortClause::SORT_DESC) {
            $expectedIds = array_reverse($expectedIds);
        }

        self::assertSame($expectedIds, $this->extractIds($userGroupList));
    }

    public function testFindUserGroupsReturnsOnlyReadableUserGroups(): void
    {
        $this->loginAsUserLimitedToGroupASubtree();

        $userGroupList = $this->getIbexaTestCore()->getUserService()->findUserGroups();

        self::assertSame($this->getIds(['groupA', 'groupA1', 'groupA2']), $this->extractIds($userGroupList));
        self::assertSame(3, $userGroupList->getTotalCount());
    }

    public function testFindUserGroupsOfUserReturnsOnlyReadableUserGroups(): void
    {
        $this->loginAsUserLimitedToGroupASubtree();

        $userGroupList = $this->getIbexaTestCore()->getUserService()->findUserGroups(
            new UserGroupQuery(new UserId($this->ids['bob']))
        );

        self::assertSame($this->getIds(['groupA']), $this->extractIds($userGroupList));
        self::assertSame(1, $userGroupList->getTotalCount());
    }

    public function testFindUserGroupsByUnreadableReferencesReturnsEmptyResult(): void
    {
        $this->loginAsUserLimitedToGroupASubtree();
        $userService = $this->getIbexaTestCore()->getUserService();

        $byUnreadableUser = $userService->findUserGroups(new UserGroupQuery(new UserId($this->ids['carol'])));
        $byUnreadableParent = $userService->findUserGroups(
            new UserGroupQuery(new ParentUserGroupId(self::MAIN_USER_GROUP_ID))
        );

        self::assertSame(0, $byUnreadableUser->getTotalCount());
        self::assertSame([], $this->extractIds($byUnreadableUser));
        self::assertSame(0, $byUnreadableParent->getTotalCount());
        self::assertSame([], $this->extractIds($byUnreadableParent));
    }

    public function testFindUserGroupsWithParentUserGroupCriterionUsedTwiceThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->getIbexaTestCore()->getUserService()->findUserGroups(
            new UserGroupQuery(new LogicalAnd([
                new ParentUserGroupId($this->ids['groupA']),
                new LogicalAnd([new ParentUserGroupId($this->ids['groupB'])]),
            ]))
        );
    }

    /**
     * @return list<int>
     */
    private function extractIds(UserGroupList $userGroupList): array
    {
        return array_map(
            static fn (UserGroup $userGroup): int => $userGroup->getId(),
            $userGroupList->getUserGroups()
        );
    }
}
