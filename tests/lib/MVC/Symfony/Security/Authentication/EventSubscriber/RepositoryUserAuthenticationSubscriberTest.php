<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Core\MVC\Symfony\Security\Authentication\EventSubscriber;

use Exception;
use Ibexa\Contracts\Core\Repository\Exceptions\PasswordInUnsupportedFormatException;
use Ibexa\Contracts\Core\Repository\UserService;
use Ibexa\Core\MVC\Symfony\Security\Authentication\EventSubscriber\RepositoryUserAuthenticationSubscriber;
use Ibexa\Core\MVC\Symfony\Security\User;
use Ibexa\Core\MVC\Symfony\Security\UserInterface as IbexaUserInterface;
use Ibexa\Core\Repository\User\Exception\UnsupportedPasswordHashType;
use Ibexa\Core\Repository\Values\User\User as APIUser;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Http\Authenticator\AuthenticatorInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Symfony\Component\Security\Http\Event\CheckPassportEvent;
use Symfony\Component\Stopwatch\Stopwatch;

final class RepositoryUserAuthenticationSubscriberTest extends TestCase
{
    private const int UNSUPPORTED_USER_PASSWORD_HASH_TYPE = 5;

    public function testGetSubscribedEvents(): void
    {
        self::assertEquals(
            [
                CheckPassportEvent::class => ['validateRepositoryUser'],
            ],
            $this->getSubscriber()->getSubscribedEvents()
        );
    }

    public function testCheckAuthenticationFailedWhenPasswordInUnsupportedFormat(): void
    {
        $apiUser = new APIUser();
        $user = $this->createMock(User::class);
        $user
            ->expects(self::once())
            ->method('getAPIUser')
            ->willReturn($apiUser);
        $user
            ->method('getPassword')
            ->willReturn('my_password');

        $userService = $this->createMock(UserService::class);
        $userService
            ->expects(self::once())
            ->method('checkUserCredentials')
            ->with($apiUser, 'my_password')
            ->willThrowException(
                new UnsupportedPasswordHashType(self::UNSUPPORTED_USER_PASSWORD_HASH_TYPE)
            );

        $this->expectException(PasswordInUnsupportedFormatException::class);

        $this->getSubscriber($userService)->validateRepositoryUser(
            $this->getCheckPassportEvent($user)
        );
    }

    public function testAuthenticateInConstantTime(): void
    {
        $constantAuthTime = 1.0;
        $stopwatch = new Stopwatch();
        $stopwatch->start('authenticate_constant_time_test');

        try {
            $this->getSubscriber(null, $constantAuthTime)->validateRepositoryUser(
                $this->getCheckPassportEvent()
            );
        } catch (Exception) {
            self::fail();
        }

        $duration = $stopwatch->stop('authenticate_constant_time_test')->getDuration();

        self::assertGreaterThanOrEqual($constantAuthTime * 1000, $duration);
    }

    public function testAuthenticateWarningOnConstantTimeExceeded(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger
            ->expects(self::once())
            ->method('warning')
            ->with('Authentication took longer than the configured constant time. Consider increasing the value of ' . RepositoryUserAuthenticationSubscriber::CONSTANT_AUTH_TIME_SETTING);

        // constant auth time is much too short, but not zero, which would disable the check
        $this->getSubscriber(null, 0.0000001, $logger)->validateRepositoryUser(
            $this->getCheckPassportEvent()
        );
    }

    public function testAuthenticateConstantTimeDisabled(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::never())->method('warning');

        $this->getSubscriber(null, 0.0, $logger)->validateRepositoryUser(
            $this->getCheckPassportEvent()
        );
    }

    public function testSkippingRepositoryUserValidationForSelfValidatingPassport(): void
    {
        $user = $this->createMock(User::class);
        $user->expects(self::never())->method('getAPIUser');

        $userService = $this->createMock(UserService::class);
        $userService->expects(self::never())->method('checkUserCredentials');

        $selfValidatingPassport = new SelfValidatingPassport(new UserBadge('foo'));

        $this->getSubscriber($userService)->validateRepositoryUser(
            $this->getCheckPassportEvent($user, $selfValidatingPassport)
        );
    }

    private function getSubscriber(
        ?UserService $userService = null,
        float $constantAuthTime = 1.0,
        ?LoggerInterface $logger = null
    ): RepositoryUserAuthenticationSubscriber {
        $request = $this->createMock(Request::class);
        $requestStack = $this->createMock(RequestStack::class);
        $requestStack
            ->method('getCurrentRequest')
            ->willReturn($request);

        return new RepositoryUserAuthenticationSubscriber(
            $requestStack,
            $userService ?? $this->createMock(UserService::class),
            $constantAuthTime,
            $logger ?? $this->createMock(LoggerInterface::class)
        );
    }

    private function getCheckPassportEvent(
        (User & MockObject) | null $user = null,
        ?Passport $passport = null,
    ): CheckPassportEvent {
        $authenticator = $this->createMock(AuthenticatorInterface::class);
        $user = $user ?? $this->createMock(User::class);

        if ($passport === null) {
            $userProvider = $this->createMock(User\APIUserProviderInterface::class);
            $userProvider
                ->expects(self::once())
                ->method('loadUserByIdentifier')
                ->willReturn($user);

            $passport = new Passport(
                new UserBadge(
                    $user->getUserIdentifier(),
                    static fn (string $userIdentifier): IbexaUserInterface => $userProvider->loadUserByIdentifier($userIdentifier)
                ),
                new PasswordCredentials($user->getPassword())
            );
        }

        return new CheckPassportEvent($authenticator, $passport);
    }
}
