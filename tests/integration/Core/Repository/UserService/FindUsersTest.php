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
use Ibexa\Contracts\Core\Repository\Values\Content\Query\SortClause;
use Ibexa\Contracts\Core\Repository\Values\Filter\Filter;
use Ibexa\Contracts\Core\Repository\Values\Filter\FilteringCriterion;
use Ibexa\Contracts\Core\Repository\Values\User\User;
use Ibexa\Contracts\Core\Repository\Values\User\UserList;

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

        yield 'parent Location of a user group with users and sub-groups' => [
            static fn (array $ids, array $locationIds): FilteringCriterion => new Criterion\ParentLocationId(
                $locationIds['groupA']
            ),
            ['alice', 'bob', 'dave'],
        ];

        yield 'parent Location of a user group holding a non-main Location of a user' => [
            static fn (array $ids, array $locationIds): FilteringCriterion => new Criterion\ParentLocationId(
                $locationIds['groupB']
            ),
            ['bob', 'carol'],
        ];

        yield 'parent Location and logins' => [
            static fn (array $ids, array $locationIds): FilteringCriterion => new Criterion\LogicalAnd([
                new Criterion\ParentLocationId($locationIds['groupB']),
                new Criterion\UserLogin(['alice', 'bob', 'carol']),
            ]),
            ['bob', 'carol'],
        ];

        yield 'match none' => [
            static fn (): FilteringCriterion => new Criterion\MatchNone(),
            [],
        ];
    }

    public function testUserWithSeveralLocationsIsReturnedOnce(): void
    {
        $userList = $this->getIbexaTestCore()->getUserService()->findUsers(new Filter(
            new Criterion\ParentLocationId([
                $this->mainLocationIds['groupA'],
                $this->mainLocationIds['groupB'],
            ])
        ));

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
        $filter = new Filter(new Criterion\ParentLocationId($this->mainLocationIds['groupA']));
        $filter->sliceBy($limit, $offset);

        $userList = $this->getIbexaTestCore()->getUserService()->findUsers($filter);

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
        // all the created users are in the same Section, so the order is decided by the content ID
        $userList = $this->getIbexaTestCore()->getUserService()->findUsers(new Filter(
            new Criterion\UserLogin(self::CREATED_USERS),
            [new SortClause\SectionIdentifier()]
        ));

        $expectedIds = $this->getIds(self::CREATED_USERS);
        sort($expectedIds);

        self::assertSame($expectedIds, $this->extractIds($userList));
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

    public function testFindUsersReturnsUsersReadableThroughSecondaryLocation(): void
    {
        $this->loginAsUserLimitedToSubtreeOf('groupB');

        $userList = $this->getIbexaTestCore()->getUserService()->findUsers(new Filter());

        // the main Location of "bob" is in "groupA", the secondary one in "groupB"
        self::assertSame($this->getIds(['bob', 'carol']), $this->extractIds($userList));
        self::assertSame(2, $userList->getTotalCount());
    }

    public function testFindUsersAsAnonymousUser(): void
    {
        $this->getIbexaTestCore()->setAnonymousUser();

        $userList = $this->getIbexaTestCore()->getUserService()->findUsers(new Filter());

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
