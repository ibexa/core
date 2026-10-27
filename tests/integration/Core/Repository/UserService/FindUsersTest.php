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
use Ibexa\Contracts\Core\Repository\Values\User\User;
use Ibexa\Contracts\Core\Repository\Values\User\UserList;

/**
 * @covers \Ibexa\Contracts\Core\Repository\UserService::findUsers
 */
final class FindUsersTest extends AbstractFindTestCase
{
    private const FIXTURE_USER_IDS = [10, self::ADMIN_USER_ID];
    private const CREATED_USERS = ['alice', 'bob', 'carol', 'dave', 'erin'];

    public function testFindUsersWithoutQueryReturnsAllUsersSortedById(): void
    {
        $userList = $this->getIbexaTestCore()->getUserService()->findUsers();

        $expectedIds = [...self::FIXTURE_USER_IDS, ...$this->getIds(self::CREATED_USERS)];
        sort($expectedIds);

        self::assertSame(count($expectedIds), $userList->getTotalCount());
        self::assertSame($expectedIds, $this->extractIds($userList));
        self::assertContainsOnlyInstancesOf(User::class, $userList);
    }

    /**
     * @param Closure(array<string, int>, array<string, int>): CriterionInterface $filterFactory
     * @param list<string> $expectedAliases
     *
     * @dataProvider provideForTestFindUsersByFilter
     */
    public function testFindUsersByFilter(
        Closure $filterFactory,
        array $expectedAliases
    ): void {
        $userList = $this->getIbexaTestCore()->getUserService()->findUsers(
            new Query(['filter' => $filterFactory($this->ids, $this->mainLocationIds)])
        );

        $expectedIds = $this->getIds($expectedAliases);
        sort($expectedIds);

        self::assertSame($expectedIds, $this->extractIds($userList));
        self::assertSame(count($expectedIds), $userList->getTotalCount());
    }

    /**
     * @return iterable<string, array{Closure(array<string, int>, array<string, int>): CriterionInterface, list<string>}>
     */
    public static function provideForTestFindUsersByFilter(): iterable
    {
        yield 'content ids' => [
            static fn (array $ids): CriterionInterface => new Criterion\ContentId([$ids['alice'], $ids['carol']]),
            ['alice', 'carol'],
        ];

        yield 'content id of a user group' => [
            static fn (array $ids): CriterionInterface => new Criterion\ContentId($ids['groupA']),
            [],
        ];

        yield 'logins' => [
            static fn (): CriterionInterface => new Criterion\UserLogin(['bob', 'carol']),
            ['bob', 'carol'],
        ];

        yield 'email' => [
            static fn (): CriterionInterface => new Criterion\UserEmail('carol@mail.invalid'),
            ['carol'],
        ];

        yield 'parent Location of a user group with users and sub-groups' => [
            static fn (array $ids, array $locationIds): CriterionInterface => new Criterion\ParentLocationId(
                $locationIds['groupA']
            ),
            ['alice', 'bob', 'dave'],
        ];

        yield 'parent Location of a user group holding a non-main Location of a user' => [
            static fn (array $ids, array $locationIds): CriterionInterface => new Criterion\ParentLocationId(
                $locationIds['groupB']
            ),
            ['bob', 'carol'],
        ];

        yield 'parent Location and logins' => [
            static fn (array $ids, array $locationIds): CriterionInterface => new Criterion\LogicalAnd([
                new Criterion\ParentLocationId($locationIds['groupB']),
                new Criterion\UserLogin(['alice', 'bob', 'carol']),
            ]),
            ['bob', 'carol'],
        ];

        yield 'match none' => [
            static fn (): CriterionInterface => new Criterion\MatchNone(),
            [],
        ];
    }

    public function testUserWithSeveralLocationsIsReturnedOnce(): void
    {
        $userList = $this->getIbexaTestCore()->getUserService()->findUsers(new Query([
            'filter' => new Criterion\ParentLocationId([
                $this->mainLocationIds['groupA'],
                $this->mainLocationIds['groupB'],
            ]),
        ]));

        self::assertSame($this->getIds(['alice', 'bob', 'carol', 'dave']), $this->extractIds($userList));
        self::assertSame(4, $userList->getTotalCount());
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
        $userList = $this->getIbexaTestCore()->getUserService()->findUsers(new Query([
            'filter' => new Criterion\ParentLocationId($this->mainLocationIds['groupA']),
            'offset' => $offset,
            'limit' => $limit,
        ]));

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
        yield 'count only' => [0, 0, []];
    }

    /**
     * @testWith ["ascending"]
     *           ["descending"]
     */
    public function testSortByContentId(string $direction): void
    {
        $userList = $this->getIbexaTestCore()->getUserService()->findUsers(new Query([
            'filter' => new Criterion\UserLogin(self::CREATED_USERS),
            'sortClauses' => [new SortClause\ContentId($direction)],
        ]));

        $expectedIds = $this->getIds(self::CREATED_USERS);
        sort($expectedIds);
        if ($direction === Query::SORT_DESC) {
            $expectedIds = array_reverse($expectedIds);
        }

        self::assertSame($expectedIds, $this->extractIds($userList));
    }

    public function testContentIdIsTheTieBreakOfTheGivenSortClauses(): void
    {
        // all the created users are in the same Section, so the order is decided by the content ID
        $userList = $this->getIbexaTestCore()->getUserService()->findUsers(new Query([
            'filter' => new Criterion\UserLogin(self::CREATED_USERS),
            'sortClauses' => [new SortClause\SectionIdentifier()],
        ]));

        $expectedIds = $this->getIds(self::CREATED_USERS);
        sort($expectedIds);

        self::assertSame($expectedIds, $this->extractIds($userList));
    }

    public function testFindUsersDoesNotChangeTheGivenQuery(): void
    {
        $filter = new Criterion\UserLogin('alice');
        $query = new Query(['filter' => $filter]);

        $this->getIbexaTestCore()->getUserService()->findUsers($query);

        self::assertSame($filter, $query->filter);
        self::assertSame([], $query->sortClauses);
    }

    public function testFindUsersReturnsOnlyReadableUsers(): void
    {
        $this->loginAsUserLimitedToGroupASubtree();

        $userList = $this->getIbexaTestCore()->getUserService()->findUsers();

        // "bob" has a Location in "groupA", "carol" and the fixture users are outside "groupA" subtree
        self::assertSame($this->getIds(['alice', 'bob', 'dave', 'erin']), $this->extractIds($userList));
        self::assertSame(4, $userList->getTotalCount());
    }

    public function testFindUsersAsAnonymousUser(): void
    {
        $this->getIbexaTestCore()->setAnonymousUser();

        $userList = $this->getIbexaTestCore()->getUserService()->findUsers();

        // anonymous users can read the content of the Standard section only
        self::assertSame([], $this->extractIds($userList));
        self::assertSame(0, $userList->getTotalCount());
    }

    /**
     * @return list<int>
     */
    private function extractIds(UserList $userList): array
    {
        return array_map(static fn (User $user): int => $user->getId(), $userList->getUsers());
    }
}
