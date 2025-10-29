<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Core\MVC\Symfony\Security;

use Ibexa\Contracts\Core\Repository\Values\User\User as APIUser;
use Ibexa\Core\MVC\Symfony\Security\UserInterface;
use Ibexa\Core\MVC\Symfony\Security\UserWrapped;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\User\EquatableInterface;
use Symfony\Component\Security\Core\User\UserInterface as SymfonyUserInterface;

final class UserWrappedTest extends TestCase
{
    private APIUser & MockObject $apiUser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->apiUser = $this->createMock(APIUser::class);
    }

    public function testGetSetAPIUser(): void
    {
        $originalUser = $this->createMock(SymfonyUserInterface::class);
        $userWrapped = new UserWrapped($originalUser, $this->apiUser);
        self::assertSame($this->apiUser, $userWrapped->getAPIUser());

        $newApiUser = $this->createMock(APIUser::class);
        $userWrapped->setAPIUser($newApiUser);
        self::assertSame($newApiUser, $userWrapped->getAPIUser());
    }

    public function testGetSetWrappedUser(): void
    {
        $originalUser = $this->createMock(SymfonyUserInterface::class);
        $userWrapped = new UserWrapped($originalUser, $this->apiUser);
        self::assertSame($originalUser, $userWrapped->getWrappedUser());

        $newWrappedUser = $this->createMock(UserInterface::class);
        $userWrapped->setWrappedUser($newWrappedUser);
        self::assertSame($newWrappedUser, $userWrapped->getWrappedUser());
    }

    public function testRegularUser(): void
    {
        $originalUser = $this->createMock(SymfonyUserInterface::class);
        $user = new UserWrapped($originalUser, $this->apiUser);

        self::assertTrue($user->isEqualTo($this->createMock(SymfonyUserInterface::class)));

        $originalUser
            ->expects(self::once())
            ->method('eraseCredentials');
        $user->eraseCredentials();

        $username = 'lolautruche';
        $roles = ['ROLE_USER', 'ROLE_TEST'];
        $originalUser
            ->expects(self::exactly(2))
            ->method('getUserIdentifier')
            ->willReturn($username);
        $originalUser
            ->expects(self::once())
            ->method('getRoles')
            ->willReturn($roles);

        self::assertSame($username, $user->getUserIdentifier());
        self::assertSame($username, (string)$user);
        self::assertSame($roles, $user->getRoles());
        self::assertSame($originalUser, $user->getWrappedUser());
    }

    public function testIsEqualTo()
    {
        $originalUser = $this->createMock(UserEquatableInterface::class);
        $user = new UserWrapped($originalUser, $this->apiUser);
        $otherUser = $this->createMock(SymfonyUserInterface::class);
        $originalUser
            ->expects(self::once())
            ->method('isEqualTo')
            ->with($otherUser)
            ->will(self::returnValue(false));
        self::assertFalse($user->isEqualTo($otherUser));
    }

    public function testNotSerializeApiUser(): void
    {
        $originalUser = $this->createMock(UserInterface::class);
        $user = new UserWrapped($originalUser, $this->apiUser);
        $serialized = serialize($user);
        $unserializedUser = unserialize($serialized);
        $this->expectException(\LogicException::class);
        $unserializedUser->getApiUser();
    }
}

/**
 * @internal For use with tests only
 */
interface UserEquatableInterface extends UserInterface, EquatableInterface {}
