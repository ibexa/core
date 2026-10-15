<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Integration\Core\Repository\UserService;

use Closure;
use Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException;
use Ibexa\Contracts\Core\Repository\Values\User\Query\Criterion\User\Email;
use Ibexa\Contracts\Core\Repository\Values\User\Query\Criterion\User\LogicalAnd;
use Ibexa\Contracts\Core\Repository\Values\User\Query\Criterion\User\Login;
use Ibexa\Contracts\Core\Repository\Values\User\Query\Criterion\User\UserGroupId;
use Ibexa\Contracts\Core\Repository\Values\User\Query\Criterion\User\UserId;
use Ibexa\Contracts\Core\Repository\Values\User\Query\SortClause;
use Ibexa\Contracts\Core\Repository\Values\User\Query\SortClause\Id;
use Ibexa\Contracts\Core\Repository\Values\User\Query\UserCriterionInterface;
use Ibexa\Contracts\Core\Repository\Values\User\Query\UserQuery;
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
     * @param \Closure(array<string, int>): \Ibexa\Contracts\Core\Repository\Values\User\Query\UserCriterionInterface $criterionFactory
     * @param list<string> $expectedAliases
     *
     * @dataProvider provideForTestFindUsersByCriterion
     */
    public function testFindUsersByCriterion(Closure $criterionFactory, array $expectedAliases): void
    {
        $userList = $this->getIbexaTestCore()->getUserService()->findUsers(
            new UserQuery($criterionFactory($this->ids))
        );

        $expectedIds = $this->getIds($expectedAliases);
        sort($expectedIds);

        self::assertSame($expectedIds, $this->extractIds($userList));
        self::assertSame(count($expectedIds), $userList->getTotalCount());
    }

    /**
     * @return iterable<string, array{\Closure(array<string, int>): \Ibexa\Contracts\Core\Repository\Values\User\Query\UserCriterionInterface, list<string>}>
     */
    public static function provideForTestFindUsersByCriterion(): iterable
    {
        yield 'user id' => [
            static fn (array $ids): UserCriterionInterface => new UserId($ids['alice']),
            ['alice'],
        ];

        yield 'user ids' => [
            static fn (array $ids): UserCriterionInterface => new UserId([$ids['alice'], $ids['carol']]),
            ['alice', 'carol'],
        ];

        yield 'login' => [
            static fn (): UserCriterionInterface => new Login('bob'),
            ['bob'],
        ];

        yield 'logins' => [
            static fn (): UserCriterionInterface => new Login(['bob', 'carol']),
            ['bob', 'carol'],
        ];

        yield 'email' => [
            static fn (): UserCriterionInterface => new Email('carol@mail.invalid'),
            ['carol'],
        ];

        yield 'emails' => [
            static fn (): UserCriterionInterface => new Email(['carol@mail.invalid', 'dave@mail.invalid']),
            ['carol', 'dave'],
        ];

        yield 'user group id' => [
            static fn (array $ids): UserCriterionInterface => new UserGroupId($ids['groupA']),
            ['alice', 'bob', 'dave'],
        ];

        yield 'user group id of a user assigned to it as a non-main Location' => [
            static fn (array $ids): UserCriterionInterface => new UserGroupId($ids['groupB']),
            ['bob', 'carol'],
        ];

        yield 'user group id with no direct users' => [
            static fn (array $ids): UserCriterionInterface => new UserGroupId($ids['groupA2']),
            [],
        ];

        yield 'logical and of user group id and logins' => [
            static fn (array $ids): UserCriterionInterface => new LogicalAnd([
                new UserGroupId($ids['groupB']),
                new Login(['alice', 'bob', 'carol']),
            ]),
            ['bob', 'carol'],
        ];

        yield 'nested logical and' => [
            static fn (array $ids): UserCriterionInterface => new LogicalAnd([
                new Email(['alice@mail.invalid', 'bob@mail.invalid', 'erin@mail.invalid']),
                new LogicalAnd([
                    new UserGroupId($ids['groupA']),
                    new UserId([$ids['bob'], $ids['erin']]),
                ]),
            ]),
            ['bob'],
        ];

        yield 'empty logical and' => [
            static fn (array $ids): UserCriterionInterface => new LogicalAnd([
                new LogicalAnd([]),
                new UserGroupId($ids['groupA1']),
            ]),
            ['erin'],
        ];

        yield 'non-existent user id' => [
            static fn (): UserCriterionInterface => new UserId(self::NON_EXISTENT_ID),
            [],
        ];

        yield 'empty list of logins' => [
            static fn (): UserCriterionInterface => new Login([]),
            [],
        ];

        yield 'non-existent user group id' => [
            static fn (): UserCriterionInterface => new UserGroupId(self::NON_EXISTENT_ID),
            [],
        ];

        yield 'user group id pointing to a user' => [
            static fn (array $ids): UserCriterionInterface => new UserGroupId($ids['alice']),
            [],
        ];
    }

    public function testUserAssignedToMultipleGroupsIsReturnedOnce(): void
    {
        $userList = $this->getIbexaTestCore()->getUserService()->findUsers(
            new UserQuery(new Login(['bob']), [], 0, null)
        );

        self::assertSame([$this->ids['bob']], $this->extractIds($userList));
        self::assertSame(1, $userList->getTotalCount());
    }

    /**
     * @param list<string> $expectedAliases
     *
     * @dataProvider provideForTestPagination
     */
    public function testPagination(int $offset, ?int $limit, array $expectedAliases): void
    {
        $userList = $this->getIbexaTestCore()->getUserService()->findUsers(
            new UserQuery(new UserGroupId($this->ids['groupA']), [], $offset, $limit)
        );

        self::assertSame($this->getIds($expectedAliases), $this->extractIds($userList));
        self::assertSame(3, $userList->getTotalCount());
    }

    /**
     * @return iterable<string, array{int, ?int, list<string>}>
     */
    public static function provideForTestPagination(): iterable
    {
        yield 'first page' => [0, 2, ['alice', 'bob']];
        yield 'last page' => [2, 2, ['dave']];
        yield 'offset past the end' => [10, 2, []];
        yield 'no limit' => [1, null, ['bob', 'dave']];
        yield 'count only' => [0, 0, []];
    }

    public function testPagesDoNotOverlap(): void
    {
        $userService = $this->getIbexaTestCore()->getUserService();

        $collectedIds = [];
        for ($offset = 0; $offset < 10; $offset += 3) {
            $collectedIds = [
                ...$collectedIds,
                ...$this->extractIds($userService->findUsers(new UserQuery(null, [], $offset, 3))),
            ];
        }

        $allIds = $this->extractIds($userService->findUsers(new UserQuery(null, [], 0, null)));

        self::assertSame($allIds, $collectedIds);
    }

    /**
     * @testWith ["ascending"]
     *           ["descending"]
     */
    public function testSortById(string $direction): void
    {
        $userList = $this->getIbexaTestCore()->getUserService()->findUsers(
            new UserQuery(new Login(self::CREATED_USERS), [new Id($direction)])
        );

        $expectedIds = $this->getIds(self::CREATED_USERS);
        sort($expectedIds);
        if ($direction === SortClause::SORT_DESC) {
            $expectedIds = array_reverse($expectedIds);
        }

        self::assertSame($expectedIds, $this->extractIds($userList));
    }

    public function testSortByIdDescendingWithUserGroupCriterion(): void
    {
        $userList = $this->getIbexaTestCore()->getUserService()->findUsers(
            new UserQuery(new UserGroupId($this->ids['groupA']), [new Id(SortClause::SORT_DESC)])
        );

        self::assertSame($this->getIds(['dave', 'bob', 'alice']), $this->extractIds($userList));
    }

    public function testFindUsersReturnsOnlyReadableUsers(): void
    {
        $this->loginAsUserLimitedToGroupASubtree();

        $userList = $this->getIbexaTestCore()->getUserService()->findUsers();

        // "bob" has its main Location in "groupA", "carol" and the fixture users are outside "groupA" subtree
        self::assertSame($this->getIds(['alice', 'bob', 'dave', 'erin']), $this->extractIds($userList));
        self::assertSame(4, $userList->getTotalCount());
    }

    public function testFindUsersByUnreadableUserGroupReturnsEmptyResult(): void
    {
        $this->loginAsUserLimitedToGroupASubtree();

        $userList = $this->getIbexaTestCore()->getUserService()->findUsers(
            new UserQuery(new UserGroupId($this->ids['groupB']))
        );

        self::assertSame([], $this->extractIds($userList));
        self::assertSame(0, $userList->getTotalCount());
    }

    public function testFindUsersAsAnonymousUser(): void
    {
        $this->getIbexaTestCore()->setAnonymousUser();

        $userList = $this->getIbexaTestCore()->getUserService()->findUsers();

        // anonymous users can read the content of the Standard section only
        self::assertSame([], $this->extractIds($userList));
        self::assertSame(0, $userList->getTotalCount());
    }

    public function testFindUsersWithUserGroupCriterionUsedTwiceThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->getIbexaTestCore()->getUserService()->findUsers(
            new UserQuery(new LogicalAnd([
                new UserGroupId($this->ids['groupA']),
                new UserGroupId($this->ids['groupB']),
            ]))
        );
    }

    /**
     * @return list<int>
     */
    private function extractIds(UserList $userList): array
    {
        return array_map(static fn (User $user): int => $user->getId(), $userList->getUsers());
    }
}
