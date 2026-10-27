<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Integration\Core\Repository\UserService;

use Closure;
use Ibexa\Contracts\Core\Repository\Values\Content\Query;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\CriterionInterface;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\SortClause;
use Ibexa\Contracts\Core\Repository\Values\User\UserGroup;
use Ibexa\Contracts\Core\Repository\Values\User\UserGroupList;

/**
 * @covers \Ibexa\Contracts\Core\Repository\UserService::findUserGroups
 */
final class FindUserGroupsTest extends AbstractFindTestCase
{
    private const FIXTURE_USER_GROUP_IDS = [self::MAIN_USER_GROUP_ID, 11, self::ADMINISTRATOR_USERS_GROUP_ID, 13, 42, 59];
    private const CREATED_USER_GROUPS = ['groupA', 'groupA1', 'groupA2', 'groupB'];
    private const MAIN_USER_GROUP_LOCATION_ID = 5;

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
     * @param Closure(array<string, int>, array<string, int>): CriterionInterface $filterFactory
     * @param list<string> $expectedAliases
     *
     * @dataProvider provideForTestFindUserGroupsByFilter
     */
    public function testFindUserGroupsByFilter(
        Closure $filterFactory,
        array $expectedAliases
    ): void {
        $userGroupList = $this->getIbexaTestCore()->getUserService()->findUserGroups(
            new Query(['filter' => $filterFactory($this->ids, $this->mainLocationIds)])
        );

        $expectedIds = $this->getIds($expectedAliases);
        sort($expectedIds);

        self::assertSame($expectedIds, $this->extractIds($userGroupList));
        self::assertSame(count($expectedIds), $userGroupList->getTotalCount());
    }

    /**
     * @return iterable<string, array{Closure(array<string, int>, array<string, int>): CriterionInterface, list<string>}>
     */
    public static function provideForTestFindUserGroupsByFilter(): iterable
    {
        yield 'content id of a user' => [
            static fn (array $ids): CriterionInterface => new Criterion\ContentId($ids['alice']),
            [],
        ];

        yield 'parent Location of a user group with users and sub-groups' => [
            static fn (array $ids, array $locationIds): CriterionInterface => new Criterion\ParentLocationId(
                $locationIds['groupA']
            ),
            ['groupA1', 'groupA2'],
        ];

        yield 'parent Location of a user group with no sub-groups' => [
            static fn (array $ids, array $locationIds): CriterionInterface => new Criterion\ParentLocationId(
                $locationIds['groupB']
            ),
            [],
        ];

        yield 'Locations of user groups' => [
            static fn (array $ids, array $locationIds): CriterionInterface => new Criterion\LocationId([
                $locationIds['groupA'],
                $locationIds['groupB'],
                $locationIds['alice'],
            ]),
            ['groupA', 'groupB'],
        ];

        yield 'parent Location and Locations' => [
            static fn (array $ids, array $locationIds): CriterionInterface => new Criterion\LogicalAnd([
                new Criterion\ParentLocationId(self::MAIN_USER_GROUP_LOCATION_ID),
                new Criterion\LocationId([$locationIds['groupA'], $locationIds['groupA1']]),
            ]),
            ['groupA'],
        ];
    }

    /**
     * @param list<string> $expectedAliases
     *
     * @dataProvider provideForTestPagination
     */
    public function testPagination(
        int $offset,
        int $limit,
        array $expectedAliases
    ): void {
        $userGroupList = $this->getIbexaTestCore()->getUserService()->findUserGroups(new Query([
            'filter' => new Criterion\ContentId($this->getIds(self::CREATED_USER_GROUPS)),
            'offset' => $offset,
            'limit' => $limit,
        ]));

        self::assertSame($this->getIds($expectedAliases), $this->extractIds($userGroupList));
        self::assertSame(4, $userGroupList->getTotalCount());
    }

    /**
     * @return iterable<string, array{int, int, list<string>}>
     */
    public static function provideForTestPagination(): iterable
    {
        yield 'first page' => [0, 3, ['groupA', 'groupA1', 'groupA2']];
        yield 'last page' => [3, 3, ['groupB']];
        yield 'offset past the end' => [10, 3, []];
        yield 'count only' => [0, 0, []];
    }

    /**
     * @testWith ["ascending"]
     *           ["descending"]
     */
    public function testSortByContentId(string $direction): void
    {
        $userGroupList = $this->getIbexaTestCore()->getUserService()->findUserGroups(new Query([
            'filter' => new Criterion\ParentLocationId(self::MAIN_USER_GROUP_LOCATION_ID),
            'sortClauses' => [new SortClause\ContentId($direction)],
        ]));

        $expectedIds = [11, self::ADMINISTRATOR_USERS_GROUP_ID, 13, 42, 59, ...$this->getIds(['groupA', 'groupB'])];
        sort($expectedIds);
        if ($direction === Query::SORT_DESC) {
            $expectedIds = array_reverse($expectedIds);
        }

        self::assertSame($expectedIds, $this->extractIds($userGroupList));
        self::assertSame(count($expectedIds), $userGroupList->getTotalCount());
    }

    public function testFindUserGroupsAlwaysPerformsCount(): void
    {
        $userGroupList = $this->getIbexaTestCore()->getUserService()->findUserGroups(new Query([
            'filter' => new Criterion\ContentId($this->getIds(self::CREATED_USER_GROUPS)),
            'limit' => 1,
            'performCount' => false,
        ]));

        self::assertSame($this->getIds(['groupA']), $this->extractIds($userGroupList));
        self::assertSame(4, $userGroupList->getTotalCount());
    }

    public function testFindUserGroupsReturnsOnlyReadableUserGroups(): void
    {
        $this->loginAsUserLimitedToGroupASubtree();

        $userGroupList = $this->getIbexaTestCore()->getUserService()->findUserGroups();

        self::assertSame($this->getIds(['groupA', 'groupA1', 'groupA2']), $this->extractIds($userGroupList));
        self::assertSame(3, $userGroupList->getTotalCount());
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
