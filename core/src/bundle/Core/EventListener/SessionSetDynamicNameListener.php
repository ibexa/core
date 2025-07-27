<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Bundle\Core\EventListener;

use Ibexa\Contracts\Core\SiteAccess\ConfigResolverInterface;
use Ibexa\Core\MVC\Symfony\Event\PostSiteAccessMatchEvent;
use Ibexa\Core\MVC\Symfony\MVCEvents;
use Ibexa\Core\MVC\Symfony\SiteAccess;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Session\Storage\NativeSessionStorage;
use Symfony\Component\HttpFoundation\Session\Storage\SessionStorageFactoryInterface;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * SiteAccess match listener.
 *
 * Allows to set a dynamic session name based on the siteaccess name.
 */
final readonly class SessionSetDynamicNameListener implements EventSubscriberInterface
{
    public const string MARKER = '{siteaccess_hash}';

    public const string SESSION_NAME_PREFIX = 'IBX_SESSION_ID';

    public function __construct(
        private ConfigResolverInterface $configResolver,
        private SessionStorageFactoryInterface $sessionStorageFactory
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            MVCEvents::SITEACCESS => ['onSiteAccessMatch', 250],
        ];
    }

    public function onSiteAccessMatch(PostSiteAccessMatchEvent $event): void
    {
        $request = $event->getRequest();
        $session = $request->hasSession() ? $request->getSession() : null;
        $sessionStorage = $this->sessionStorageFactory->createStorage($request);

        if (
            !(
                $event->getRequestType() === HttpKernelInterface::MAIN_REQUEST
                && $session
                && !$session->isStarted()
                && $sessionStorage instanceof NativeSessionStorage
            )
        ) {
            return;
        }

        $sessionOptions = (array)$this->configResolver->getParameter('session');
        $sessionName = $sessionOptions['name'] ?? $session->getName();
        $sessionOptions['name'] = $this->getSessionName($sessionName, $event->getSiteAccess());
        $sessionStorage->setOptions($sessionOptions);
    }

    private function getSessionName(string $sessionName, SiteAccess $siteAccess): string
    {
        // Add session prefix if needed.
        if (!str_starts_with($sessionName, self::SESSION_NAME_PREFIX)) {
            $sessionName = self::SESSION_NAME_PREFIX . '_' . $sessionName;
        }

        // Check if uniqueness marker is present. If so, session name will be unique for current siteaccess.
        if (str_contains($sessionName, self::MARKER)) {
            $sessionName = str_replace(self::MARKER, md5($siteAccess->name), $sessionName);
        }

        return $sessionName;
    }
}
