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
use Symfony\Component\Security\Http\AccessMapInterface;
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
        private readonly AccessMapInterface $map,
    ) {
    }

    public function supports(Request $request): ?bool
    {
        // The x-user-context-hash request doesn't need to go through authentication
        if ($request->getPathInfo() === '/_fos_user_context_hash') {
            return false;
        }

        if ($this->isAnonymousLoginCheckApplicable($request)) {
            [$attributes] = $this->map->getPatterns($request);
            $request->attributes->set('_access_control_attributes', $attributes);

            return true;
        }

        // The anonymous-login check does not apply here (e.g. the `ibexa_rest`
        // firewall exposes no `login_path`, the request is already authenticated,
        // or it targets the login page). Stay transparent and defer to the
        // decorated AccessListener so that `access_control` is still enforced
        // instead of being silently skipped.
        return $this->innerListener->supports($request);
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

        $request = $event->getRequest();

        // When the anonymous-login check does not apply, defer to the decorated
        // AccessListener so that standard `access_control` enforcement still runs.
        if (!$this->isAnonymousLoginCheckApplicable($request)) {
            $this->innerListener->authenticate($event);

            return;
        }

        if (
            $this->permissionResolver->canUser(
                'user',
                'login',
                $request->attributes->get('siteaccess')
            )
        ) {
            $this->innerListener->authenticate($event);

            return;
        }

        throw new AccessDeniedException('Anonymous user cannot login to the current siteaccess');
    }

    /**
     * The anonymous-login permission check only applies to anonymous requests on a
     * firewall that exposes a `login_path`, and never to the login page itself.
     */
    private function isAnonymousLoginCheckApplicable(Request $request): bool
    {
        // An already-authenticated request (e.g. Basic auth) is not an anonymous login.
        if ($request->getUser() !== null) {
            return false;
        }

        $pathInfo = $request->getPathInfo();
        $firewallConfig = $this->security->getFirewallConfig($request);
        // we only check `login_path` for the current firewall
        // e.g. `ibexa_rest` firewall won't be taken into account
        // as there is no `login_path` defined for its authenticator
        $loginPath = $firewallConfig !== null
            ? ($this->firewallLoginPaths[$firewallConfig->getName()] ?? null)
            : null;

        return $loginPath !== null && !str_ends_with($pathInfo, $loginPath);
    }
}
