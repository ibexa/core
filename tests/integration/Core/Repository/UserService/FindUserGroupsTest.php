<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Integration\Core\Repository\UserService;

use Closure;
use Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException;
use Ibexa\Contracts\Core\Repository\Exceptions\UnauthorizedException;
use Ibexa\Contracts\Core\Repository\TrashService;
use Ibexa\Contracts\Core\Repository\Values\Content\Query;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\SortClause;
use Ibexa\Contracts\Core\Repository\Values\Filter\Filter;
use Ibexa\Contracts\Core\Repository\Values\Filter\FilteringCriterion;
use Ibexa\Contracts\Core\Repository\Values\User\User;
use Ibexa\Contracts\Core\Repository\Values\User\UserGroup;

/**
 * @covers \Ibexa\Contracts\Core\Repository\UserService::findUserGroups
 */
final class FindUserGroupsTest extends AbstractFindTestCase
{
    private const FIXTURE_USER_GROUP_IDS = [self::MAIN_USER_GROUP_ID, 11, self::ADMINISTRATOR_USERS_GROUP_ID, 13, 42, 59];
    private const CREATED_USER_GROUPS = ['groupA', 'groupA1', 'groupA2', 'groupB'];
    private const MAIN_USER_GROUP_LOCATION_ID = 5;

    public function testFindUserGroupsWithoutCriterionReturnsAllUserGroupsSortedById(): void
    {
        $userGroupList = $this->getIbexaTestCore()->getUserService()->findUserGroups(new Filter());

        $expectedIds = [...self::FIXTURE_USER_GROUP_IDS, ...$this->getIds(self::CREATED_USER_GROUPS)];
        sort($expectedIds);

        self::assertSame(count($expectedIds), $userGroupList->getTotalCount());
        self::assertSame($expectedIds, $this->extractIds($userGroupList));
        self::assertContainsOnlyInstancesOf(UserGroup::class, $userGroupList);
    }

    /**
     * @param Closure(array<string, int>, array<string, int>): FilteringCriterion $criterionFactory
     * @param list<string> $expectedAliases
     *
     * @dataProvider provideForTestFindUserGroupsByCriterion
     */
    public function testFindUserGroupsByCriterion(
        Closure $criterionFactory,
        array $expectedAliases
    ): void {
        $userGroupList = $this->getIbexaTestCore()->getUserService()->findUserGroups(
            new Filter($criterionFactory($this->ids, $this->mainLocationIds))
        );

        $expectedIds = $this->getIds($expectedAliases);
        sort($expectedIds);

        self::assertSame($expectedIds, $this->extractIds($userGroupList));
        self::assertSame(count($expectedIds), $userGroupList->getTotalCount());
    }

    /**
     * @return iterable<string, array{Closure(array<string, int>, array<string, int>): FilteringCriterion, list<string>}>
     */
    public static function provideForTestFindUserGroupsByCriterion(): iterable
    {
        yield 'content id of a user' => [
            static fn (array $ids): FilteringCriterion => new Criterion\ContentId($ids['alice']),
            [],
        ];

        yield 'parent Location of a user group with users and sub-groups' => [
            static fn (array $ids, array $locationIds): FilteringCriterion => new Criterion\ParentLocationId(
                $locationIds['groupA']
            ),
            ['groupA1', 'groupA2'],
        ];

        yield 'parent Location of a user group with no sub-groups' => [
            static fn (array $ids, array $locationIds): FilteringCriterion => new Criterion\ParentLocationId(
                $locationIds['groupB']
            ),
            [],
        ];

        yield 'content ids of user groups (the user groups of a user)' => [
            static fn (array $ids): FilteringCriterion => new Criterion\ContentId([$ids['groupA'], $ids['groupB']]),
            ['groupA', 'groupB'],
        ];

        yield 'Locations of user groups' => [
            static fn (array $ids, array $locationIds): FilteringCriterion => new Criterion\LocationId([
                $locationIds['groupA'],
                $locationIds['groupB'],
                $locationIds['alice'],
            ]),
            ['groupA', 'groupB'],
        ];

        yield 'parent Location and Locations' => [
            static fn (array $ids, array $locationIds): FilteringCriterion => new Criterion\LogicalAnd([
                new Criterion\ParentLocationId(self::MAIN_USER_GROUP_LOCATION_ID),
                new Criterion\LocationId([$locationIds['groupA'], $locationIds['groupA1']]),
            ]),
            ['groupA'],
        ];
    }

    /**
     * @param (Closure(array<string, int>): FilteringCriterion)|null $criterionFactory
     * @param list<string> $expectedAliases
     *
     * @dataProvider provideForTestFindUserGroupsOfUser
     */
    public function testFindUserGroupsOfUser(
        string $userAlias,
        ?Closure $criterionFactory,
        array $expectedAliases
    ): void {
        $userGroupList = $this->getIbexaTestCore()->getUserService()->findUserGroups(
            new Filter($criterionFactory !== null ? $criterionFactory($this->ids) : null),
            $this->loadUser($userAlias)
        );

        $expectedIds = $this->getIds($expectedAliases);
        sort($expectedIds);

        self::assertSame($expectedIds, $this->extractIds($userGroupList));
        self::assertSame(count($expectedIds), $userGroupList->getTotalCount());
    }

    /**
     * @return iterable<string, array{string, (Closure(array<string, int>): FilteringCriterion)|null, list<string>}>
     */
    public static function provideForTestFindUserGroupsOfUser(): iterable
    {
        yield 'direct user group, not its parent' => ['erin', null, ['groupA1']];

        yield 'user groups of the main and a secondary Location' => ['bob', null, ['groupA', 'groupB']];

        yield 'content ids' => [
            'bob',
            static fn (array $ids): FilteringCriterion => new Criterion\ContentId([$ids['groupB'], $ids['groupA1']]),
            ['groupB'],
        ];
    }

    public function testFindUserGroupsOfUserChecksPermissionsOnEachLocation(): void
    {
        $user = $this->loadUser('bob');
        $this->loginAsUserLimitedToSubtreeOf('groupB');

        $userGroupList = $this->getIbexaTestCore()->getUserService()->findUserGroups(new Filter(), $user);

        // "bob" is readable through its secondary Location, in "groupB", but not through its main one, in "groupA"
        self::assertSame($this->getIds(['groupB']), $this->extractIds($userGroupList));
        self::assertSame(1, $userGroupList->getTotalCount());
    }

    public function testFindUserGroupsOfUnreadableUserThrowsUnauthorizedException(): void
    {
        $user = $this->loadUser('alice');
        $this->loginAsUserLimitedToSubtreeOf('groupB');

        $this->expectException(UnauthorizedException::class);

        $this->getIbexaTestCore()->getUserService()->findUserGroups(new Filter(), $user);
    }

    public function testFindUserGroupsOfDeletedUserThrowsNotFoundException(): void
    {
        $userService = $this->getIbexaTestCore()->getUserService();
        $user = $this->loadUser('carol');
        $userService->deleteUser($user);

        $this->expectException(NotFoundException::class);

        $userService->findUserGroups(new Filter(), $user);
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
        $filter = new Filter(new Criterion\ContentId($this->getIds(self::CREATED_USER_GROUPS)));
        $filter->sliceBy($limit, $offset);

        $userGroupList = $this->getIbexaTestCore()->getUserService()->findUserGroups($filter);

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
        yield 'no limit' => [0, 0, self::CREATED_USER_GROUPS];
    }

    /**
     * @testWith ["ascending"]
     *           ["descending"]
     */
    public function testSortByContentId(string $direction): void
    {
        $userGroupList = $this->getIbexaTestCore()->getUserService()->findUserGroups(new Filter(
            new Criterion\ParentLocationId(self::MAIN_USER_GROUP_LOCATION_ID),
            [new SortClause\ContentId($direction)]
        ));

        $expectedIds = [11, self::ADMINISTRATOR_USERS_GROUP_ID, 13, 42, 59, ...$this->getIds(['groupA', 'groupB'])];
        sort($expectedIds);
        if ($direction === Query::SORT_DESC) {
            $expectedIds = array_reverse($expectedIds);
        }

        self::assertSame($expectedIds, $this->extractIds($userGroupList));
        self::assertSame(count($expectedIds), $userGroupList->getTotalCount());
    }

    public function testFindUserGroupsReturnsOnlyReadableUserGroups(): void
    {
        $this->loginAsUserLimitedToSubtreeOf('groupA');

        $userGroupList = $this->getIbexaTestCore()->getUserService()->findUserGroups(new Filter());

        self::assertSame($this->getIds(['groupA', 'groupA1', 'groupA2']), $this->extractIds($userGroupList));
        self::assertSame(3, $userGroupList->getTotalCount());
    }

    /**
     * @param list<string> $prioritizedLanguages
     * @param list<string> $expectedNames
     *
     * @dataProvider provideForTestFindUserGroupsInPrioritizedLanguages
     */
    public function testFindUserGroupsInPrioritizedLanguages(
        array $prioritizedLanguages,
        array $expectedNames
    ): void {
        $userGroupIds = [
            'always_available' => $this->createEnglishUserGroup('always_available', true)->getId(),
            'not_always_available' => $this->createEnglishUserGroup('not_always_available', false)->getId(),
        ];

        $userGroupList = $this->getIbexaTestCore()->getUserService()->findUserGroups(
            new Filter(new Criterion\ContentId(array_values($userGroupIds))),
            null,
            $prioritizedLanguages
        );

        $expectedIds = array_map(static fn (string $name): int => $userGroupIds[$name], $expectedNames);

        self::assertSame($expectedIds, $this->extractIds($userGroupList));
        self::assertSame(count($expectedIds), $userGroupList->getTotalCount());
    }

    /**
     * @return iterable<string, array{list<string>, list<string>}>
     */
    public static function provideForTestFindUserGroupsInPrioritizedLanguages(): iterable
    {
        yield 'a language the user groups are not translated to' => [
            ['eng-US'],
            ['always_available'],
        ];

        yield 'languages including the one the user groups are translated to' => [
            ['eng-US', 'eng-GB'],
            ['always_available', 'not_always_available'],
        ];
    }

    public function testFindUserGroupsSkipsTrashedUserGroups(): void
    {
        $this->getIbexaTestCore()->getServiceByClassName(TrashService::class)->trash(
            $this->getIbexaTestCore()->getLocationService()->loadLocation($this->mainLocationIds['groupA2'])
        );

        $userGroupList = $this->getIbexaTestCore()->getUserService()->findUserGroups(
            new Filter(new Criterion\ContentId($this->getIds(self::CREATED_USER_GROUPS)))
        );

        self::assertSame($this->getIds(['groupA', 'groupA1', 'groupB']), $this->extractIds($userGroupList));
        self::assertSame(3, $userGroupList->getTotalCount());
    }

    private function loadUser(string $alias): User
    {
        return $this->getIbexaTestCore()->getUserService()->loadUser($this->ids[$alias]);
    }

    private function createEnglishUserGroup(
        string $name,
        bool $alwaysAvailable
    ): UserGroup {
        $userService = $this->getIbexaTestCore()->getUserService();

        $userGroupCreateStruct = $userService->newUserGroupCreateStruct('eng-GB');
        $userGroupCreateStruct->alwaysAvailable = $alwaysAvailable;
        $userGroupCreateStruct->setField('name', $name);

        return $userService->createUserGroup(
            $userGroupCreateStruct,
            $userService->loadUserGroup($this->ids['groupB'])
        );
    }
}
