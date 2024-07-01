<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\MVC\Symfony\Security\Authentication\EventSubscriber;

use Ibexa\Contracts\Core\Repository\PermissionResolver;
use Ibexa\Core\MVC\Symfony\Security\UserInterface as IbexaUser;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Http\Event\AuthenticationTokenCreatedEvent;

final readonly class OnAuthenticationTokenCreatedRepositoryUserSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private PermissionResolver $permissionResolver,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            AuthenticationTokenCreatedEvent::class => ['onAuthenticationTokenCreated', 10],
        ];
    }

    public function onAuthenticationTokenCreated(AuthenticationTokenCreatedEvent $event): void
    {
        $user = $event->getAuthenticatedToken()->getUser();
        if (!$user instanceof IbexaUser) {
            return;
        }

        $this->permissionResolver->setCurrentUserReference($user->getAPIUser());
    }
}
