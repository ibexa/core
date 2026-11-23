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
use Ibexa\Contracts\Core\Repository\Values\Content\Query;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\SortClause;
use Ibexa\Contracts\Core\Repository\Values\Filter\Filter;
use Ibexa\Contracts\Core\Repository\Values\Filter\FilteringCriterion;
use Ibexa\Contracts\Core\Repository\Values\User\User;
use Ibexa\Contracts\Core\Repository\Values\User\UserGroup;

/**
 * @covers \Ibexa\Contracts\Core\Repository\UserService::findUsers
 */
final class FindUsersTest extends AbstractFindTestCase
{
    private const FIXTURE_USER_IDS = [10, self::ADMIN_USER_ID];
    private const CREATED_USERS = ['alice', 'bob', 'carol', 'dave', 'erin'];

    public function testFindUsersWithoutCriterionReturnsAllUsersSortedById(): void
    {
        $userList = $this->getIbexaTestCore()->getUserService()->findUsers(new Filter());

        $expectedIds = [...self::FIXTURE_USER_IDS, ...$this->getIds(self::CREATED_USERS)];
        sort($expectedIds);

        self::assertSame(count($expectedIds), $userList->getTotalCount());
        self::assertSame($expectedIds, $this->extractIds($userList));
        self::assertContainsOnlyInstancesOf(User::class, $userList);
    }

    /**
     * @param Closure(array<string, int>, array<string, int>): FilteringCriterion $criterionFactory
     * @param list<string> $expectedAliases
     *
     * @dataProvider provideForTestFindUsersByCriterion
     */
    public function testFindUsersByCriterion(
        Closure $criterionFactory,
        array $expectedAliases
    ): void {
        $userList = $this->getIbexaTestCore()->getUserService()->findUsers(
            new Filter($criterionFactory($this->ids, $this->mainLocationIds))
        );

        $expectedIds = $this->getIds($expectedAliases);
        sort($expectedIds);

        self::assertSame($expectedIds, $this->extractIds($userList));
        self::assertSame(count($expectedIds), $userList->getTotalCount());
    }

    /**
     * @return iterable<string, array{Closure(array<string, int>, array<string, int>): FilteringCriterion, list<string>}>
     */
    public static function provideForTestFindUsersByCriterion(): iterable
    {
        yield 'content ids' => [
            static fn (array $ids): FilteringCriterion => new Criterion\ContentId([$ids['alice'], $ids['carol']]),
            ['alice', 'carol'],
        ];

        yield 'content id of a user group' => [
            static fn (array $ids): FilteringCriterion => new Criterion\ContentId($ids['groupA']),
            [],
        ];

        yield 'logins' => [
            static fn (): FilteringCriterion => new Criterion\UserLogin(['bob', 'carol']),
            ['bob', 'carol'],
        ];

        yield 'login pattern' => [
            static fn (): FilteringCriterion => new Criterion\UserLogin('ca*', Criterion\Operator::LIKE),
            ['carol'],
        ];

        yield 'email' => [
            static fn (): FilteringCriterion => new Criterion\UserEmail('carol@mail.invalid'),
            ['carol'],
        ];

        yield 'email pattern' => [
            static fn (): FilteringCriterion => new Criterion\UserEmail('*@mail.invalid', Criterion\Operator::LIKE),
            self::CREATED_USERS,
        ];

        yield 'parent Location matched against the main Location only' => [
            static fn (array $ids, array $locationIds): FilteringCriterion => new Criterion\ParentLocationId(
                $locationIds['groupB']
            ),
            ['carol'],
        ];

        yield 'match none' => [
            static fn (): FilteringCriterion => new Criterion\MatchNone(),
            [],
        ];
    }

    public function testUserWithSeveralLocationsIsReturnedOnce(): void
    {
        $userList = $this->getIbexaTestCore()->getUserService()->findUsers(new Filter(new Criterion\UserLogin('bob')));

        self::assertSame($this->getIds(['bob']), $this->extractIds($userList));
        self::assertSame(1, $userList->getTotalCount());
    }

    /**
     * @param (Closure(array<string, int>): FilteringCriterion)|null $criterionFactory
     * @param list<string> $expectedAliases
     *
     * @dataProvider provideForTestFindUsersOfUserGroup
     */
    public function testFindUsersOfUserGroup(
        string $userGroupAlias,
        ?Closure $criterionFactory,
        array $expectedAliases
    ): void {
        $userList = $this->getIbexaTestCore()->getUserService()->findUsers(
            new Filter($criterionFactory !== null ? $criterionFactory($this->ids) : null),
            $this->loadUserGroup($userGroupAlias)
        );

        $expectedIds = $this->getIds($expectedAliases);
        sort($expectedIds);

        self::assertSame($expectedIds, $this->extractIds($userList));
        self::assertSame(count($expectedIds), $userList->getTotalCount());
    }

    /**
     * @return iterable<string, array{string, (Closure(array<string, int>): FilteringCriterion)|null, list<string>}>
     */
    public static function provideForTestFindUsersOfUserGroup(): iterable
    {
        yield 'direct members, not the ones of sub-groups' => ['groupA', null, ['alice', 'bob', 'dave']];

        yield 'members assigned through a secondary Location' => ['groupB', null, ['bob', 'carol']];

        yield 'user group without members' => ['groupA2', null, []];

        yield 'logins' => [
            'groupB',
            static fn (): FilteringCriterion => new Criterion\UserLogin(['alice', 'bob']),
            ['bob'],
        ];

        yield 'email' => [
            'groupB',
            static fn (): FilteringCriterion => new Criterion\UserEmail('carol@mail.invalid'),
            ['carol'],
        ];

        yield 'content ids' => [
            'groupA',
            static fn (array $ids): FilteringCriterion => new Criterion\ContentId([$ids['alice'], $ids['carol']]),
            ['alice'],
        ];

        yield 'Location criterion evaluated on the Location in the user group' => [
            'groupB',
            static fn (): FilteringCriterion => new Criterion\Location\IsMainLocation(
                Criterion\Location\IsMainLocation::NOT_MAIN
            ),
            ['bob'],
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
        $filter = new Filter();
        $filter->sliceBy($limit, $offset);

        $userList = $this->getIbexaTestCore()->getUserService()->findUsers($filter, $this->loadUserGroup('groupA'));

        self::assertSame($this->getIds($expectedAliases), $this->extractIds($userList));
        self::assertSame(3, $userList->getTotalCount());
    }

    /**
     * @return iterable<string, array{int, int, list<string>}>
     */
    public static function provideForTestPagination(): iterable
    {
        yield 'first page' => [0, 2, ['alice', 'bob']];
        yield 'last page' => [2, 2, ['dave']];
        yield 'offset past the end' => [10, 2, []];
        yield 'no limit' => [0, 0, ['alice', 'bob', 'dave']];
    }

    /**
     * @testWith ["ascending"]
     *           ["descending"]
     */
    public function testSortByContentId(string $direction): void
    {
        $userList = $this->getIbexaTestCore()->getUserService()->findUsers(new Filter(
            new Criterion\UserLogin(self::CREATED_USERS),
            [new SortClause\ContentId($direction)]
        ));

        $expectedIds = $this->getIds(self::CREATED_USERS);
        sort($expectedIds);
        if ($direction === Query::SORT_DESC) {
            $expectedIds = array_reverse($expectedIds);
        }

        self::assertSame($expectedIds, $this->extractIds($userList));
    }

    public function testContentIdIsTheTieBreakOfTheGivenSortClauses(): void
    {
        // both users are in the same Section, so the order is decided by the content ID, although in "groupB" the
        // Location of "bob" was created after the one of "carol"
        $userList = $this->getIbexaTestCore()->getUserService()->findUsers(
            new Filter(null, [new SortClause\SectionIdentifier()]),
            $this->loadUserGroup('groupB')
        );

        self::assertSame($this->getIds(['bob', 'carol']), $this->extractIds($userList));
    }

    public function testFindUsersDoesNotChangeTheGivenFilter(): void
    {
        $criterion = new Criterion\UserLogin('alice');
        $filter = new Filter($criterion);

        $this->getIbexaTestCore()->getUserService()->findUsers($filter);

        self::assertSame($criterion, $filter->getCriterion());
        self::assertSame([], $filter->getSortClauses());
    }

    public function testFindUsersReturnsOnlyReadableUsers(): void
    {
        $this->loginAsUserLimitedToSubtreeOf('groupA');

        $userList = $this->getIbexaTestCore()->getUserService()->findUsers(new Filter());

        // "carol" and the fixture users are outside "groupA" subtree
        self::assertSame($this->getIds(['alice', 'bob', 'dave', 'erin']), $this->extractIds($userList));
        self::assertSame(4, $userList->getTotalCount());
    }

    public function testFindUsersChecksPermissionsOnTheMainLocation(): void
    {
        $this->loginAsUserLimitedToSubtreeOf('groupB');

        $userList = $this->getIbexaTestCore()->getUserService()->findUsers(new Filter());

        // the main Location of "bob" is in "groupA", only the secondary one is in "groupB"
        self::assertSame($this->getIds(['carol']), $this->extractIds($userList));
        self::assertSame(1, $userList->getTotalCount());
    }

    public function testFindUsersOfUserGroupChecksPermissionsOnTheLocationInTheUserGroup(): void
    {
        $userGroup = $this->loadUserGroup('groupB');
        $this->loginAsUserLimitedToSubtreeOf('groupB');

        $userList = $this->getIbexaTestCore()->getUserService()->findUsers(new Filter(), $userGroup);

        // "bob" is readable through its secondary Location, in "groupB"
        self::assertSame($this->getIds(['bob', 'carol']), $this->extractIds($userList));
        self::assertSame(2, $userList->getTotalCount());
    }

    public function testFindUsersOfUnreadableUserGroupThrowsUnauthorizedException(): void
    {
        $userGroup = $this->loadUserGroup('groupA');
        $this->loginAsUserLimitedToSubtreeOf('groupB');

        $this->expectException(UnauthorizedException::class);

        $this->getIbexaTestCore()->getUserService()->findUsers(new Filter(), $userGroup);
    }

    public function testFindUsersOfDeletedUserGroupThrowsNotFoundException(): void
    {
        $userService = $this->getIbexaTestCore()->getUserService();
        $userGroup = $this->loadUserGroup('groupA2');
        $userService->deleteUserGroup($userGroup);

        $this->expectException(NotFoundException::class);

        $userService->findUsers(new Filter(), $userGroup);
    }

    /**
     * @param list<string> $prioritizedLanguages
     * @param list<string> $expectedLogins
     *
     * @dataProvider provideForTestFindUsersInPrioritizedLanguages
     */
    public function testFindUsersInPrioritizedLanguages(
        array $prioritizedLanguages,
        array $expectedLogins
    ): void {
        $userIds = [
            'always_available' => $this->createEnglishUser('always_available', true)->getId(),
            'not_always_available' => $this->createEnglishUser('not_always_available', false)->getId(),
        ];

        $userList = $this->getIbexaTestCore()->getUserService()->findUsers(
            new Filter(new Criterion\ContentId(array_values($userIds))),
            null,
            $prioritizedLanguages
        );

        $expectedIds = array_map(static fn (string $login): int => $userIds[$login], $expectedLogins);

        self::assertSame($expectedIds, $this->extractIds($userList));
        self::assertSame(count($expectedIds), $userList->getTotalCount());
    }

    /**
     * @return iterable<string, array{list<string>, list<string>}>
     */
    public static function provideForTestFindUsersInPrioritizedLanguages(): iterable
    {
        yield 'a language the users are not translated to' => [
            ['eng-US'],
            ['always_available'],
        ];

        yield 'languages including the one the users are translated to' => [
            ['eng-US', 'eng-GB'],
            ['always_available', 'not_always_available'],
        ];
    }

    public function testFindUsersAsAnonymousUser(): void
    {
        $this->getIbexaTestCore()->setAnonymousUser();

        $userList = $this->getIbexaTestCore()->getUserService()->findUsers(new Filter());

        // anonymous users can read the content of the Standard section only
        self::assertSame([], $this->extractIds($userList));
        self::assertSame(0, $userList->getTotalCount());
    }

    private function createEnglishUser(
        string $login,
        bool $alwaysAvailable
    ): User {
        $userService = $this->getIbexaTestCore()->getUserService();

        $userCreateStruct = $userService->newUserCreateStruct($login, "$login@mail.invalid", 'secret', 'eng-GB');
        $userCreateStruct->alwaysAvailable = $alwaysAvailable;
        $userCreateStruct->setField('first_name', $login);
        $userCreateStruct->setField('last_name', 'Doe');

        return $userService->createUser($userCreateStruct, [$this->loadUserGroup('groupB')]);
    }

    private function loadUserGroup(string $alias): UserGroup
    {
        return $this->getIbexaTestCore()->getUserService()->loadUserGroup($this->ids[$alias]);
    }
}
