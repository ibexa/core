<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Core\MVC\Symfony\Security\Authentication\EventSubscriber;

use Ibexa\Contracts\Core\Repository\PermissionResolver;
use Ibexa\Core\MVC\Symfony\Security\Authentication\EventSubscriber\OnAuthenticationTokenCreatedRepositoryUserSubscriber;
use Ibexa\Core\MVC\Symfony\Security\User;
use Ibexa\Core\Repository\Values\User\User as ApiUser;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\User\InMemoryUser;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Event\AuthenticationTokenCreatedEvent;

final class OnAuthenticationTokenCreatedRepositoryUserSubscriberTest extends TestCase
{
    public function testGetSubscribedEvents(): void
    {
        $subscriber = new OnAuthenticationTokenCreatedRepositoryUserSubscriber(
            $this->createMock(PermissionResolver::class)
        );

        self::assertEquals(
            [
                AuthenticationTokenCreatedEvent::class => ['onAuthenticationTokenCreated', 10],
            ],
            $subscriber->getSubscribedEvents()
        );
    }

    /**
     * @dataProvider dataProviderForTestSettingCurrentUserReference
     */
    public function testSettingCurrentUserReference(
        UserInterface $user,
        bool $isPermissionResolverInvoked
    ): void {
        $permissionResolver = $this->createMock(PermissionResolver::class);
        $permissionResolver
            ->expects($isPermissionResolverInvoked === true ? self::once() : self::never())
            ->method('setCurrentUserReference');

        $subscriber = new OnAuthenticationTokenCreatedRepositoryUserSubscriber($permissionResolver);

        $subscriber->onAuthenticationTokenCreated(
            $this->getAuthenticationTokenCreatedEvent($user)
        );
    }

    /**
     * @return iterable<string, array{\Symfony\Component\Security\Core\User\UserInterface, bool}>
     */
    public function dataProviderForTestSettingCurrentUserReference(): iterable
    {
        yield 'authorizing Ibexa user' => [
            new User($this->createMock(ApiUser::class)),
            true,
        ];

        yield 'authorizing non-Ibexa user' => [
            new InMemoryUser('foo', 'bar'),
            false,
        ];
    }

    private function getAuthenticationTokenCreatedEvent(UserInterface $user): AuthenticationTokenCreatedEvent
    {
        return new AuthenticationTokenCreatedEvent(
            new UsernamePasswordToken($user, 'test_firewall'),
            new Passport(
                new UserBadge('foo'),
                new PasswordCredentials('bar')
            )
        );
    }
}
