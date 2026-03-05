<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\MVC\Symfony\Security\Authentication;

use Ibexa\Contracts\Core\Repository\PermissionResolver;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Http\Firewall\AbstractListener;
use Symfony\Component\Security\Http\Firewall\AccessListener;

final class AnonymousUserAccessListener extends AbstractListener
{
    /**
     * @param string[] $firewallLoginPaths
     */
    public function __construct(
        private readonly PermissionResolver $permissionResolver,
        private readonly AccessListener $innerListener,
        private readonly Security $security,
        private readonly array $firewallLoginPaths,
    ) {
    }

    public function supports(Request $request): bool
    {
        $pathInfo = $request->getPathInfo();

        // we skip the processing in case we are authorized already or the request is the x-user-context-hash
        // which doesn't need to go through authentication
        if ($pathInfo === '/_fos_user_context_hash' || $request->getUser() !== null) {
            return false;
        }

        $firewallConfig = $this->security->getFirewallConfig($request);
        // we only check `login_path` for the current firewall
        // e.g. `ibexa_rest` firewall won't be taken into account
        // as there is no `login_path` defined for its authenticator
        $loginPath = $firewallConfig !== null
            ? ($this->firewallLoginPaths[$firewallConfig->getName()] ?? null)
            : null;

        return $loginPath !== null && !str_ends_with($pathInfo, $loginPath);
    }

    /**
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\BadStateException
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException
     */
    public function authenticate(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        if (
            $this->permissionResolver->canUser(
                'user',
                'login',
                $event->getRequest()->attributes->get('siteaccess')
            )
        ) {
            $this->innerListener->authenticate($event);

            return;
        }

        throw new AccessDeniedException('Anonymous user cannot login to the current siteaccess');
    }
}
