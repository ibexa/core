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
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\User\EquatableInterface;
use Symfony\Component\Security\Core\User\UserInterface as SymfonyUserInterface;

final class UserWrappedTest extends TestCase
{
    private APIUser&Stub $apiUser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->apiUser = self::createStub(APIUser::class);
    }

    public function testGetSetAPIUser(): void
    {
        $originalUser = self::createStub(SymfonyUserInterface::class);
        $userWrapped = new UserWrapped($originalUser, $this->apiUser);
        self::assertSame($this->apiUser, $userWrapped->getAPIUser());

        $newApiUser = self::createStub(APIUser::class);
        $userWrapped->setAPIUser($newApiUser);
        self::assertSame($newApiUser, $userWrapped->getAPIUser());
    }

    public function testGetSetWrappedUser(): void
    {
        $originalUser = self::createStub(SymfonyUserInterface::class);
        $userWrapped = new UserWrapped($originalUser, $this->apiUser);
        self::assertSame($originalUser, $userWrapped->getWrappedUser());

        $newWrappedUser = self::createStub(UserInterface::class);
        $userWrapped->setWrappedUser($newWrappedUser);
        self::assertSame($newWrappedUser, $userWrapped->getWrappedUser());
    }

    public function testRegularUser(): void
    {
        // Symfony 8 removed UserInterface::eraseCredentials(), so a plain
        // SymfonyUserInterface mock no longer has it to configure. UserWrapped::eraseCredentials()
        // still calls it via reflection when the wrapped user implements it (legacy/BC support),
        // so mock a user that still declares the method to exercise that path.
        $originalUser = $this->createMock(LegacyEraseCredentialsUserInterface::class);
        $user = new UserWrapped($originalUser, $this->apiUser);

        self::assertTrue($user->isEqualTo(self::createStub(SymfonyUserInterface::class)));

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

    public function testIsEqualTo(): void
    {
        $originalUser = $this->createMock(UserEquatableInterface::class);
        $user = new UserWrapped($originalUser, $this->apiUser);
        $otherUser = self::createStub(SymfonyUserInterface::class);
        $originalUser
            ->expects(self::once())
            ->method('isEqualTo')
            ->with($otherUser)
            ->will(self::returnValue(false));
        self::assertFalse($user->isEqualTo($otherUser));
    }

    public function testNotSerializeApiUser(): void
    {
        $originalUser = self::createStub(UserInterface::class);
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
interface UserEquatableInterface extends UserInterface, EquatableInterface
{
}

/**
 * @internal For use with tests only.
 *
 * Symfony 8 dropped eraseCredentials() from SymfonyUserInterface; this keeps it mockable
 * to exercise UserWrapped's reflection-based BC call to a legacy implementer.
 */
interface LegacyEraseCredentialsUserInterface extends SymfonyUserInterface
{
    public function eraseCredentials(): void;
}
