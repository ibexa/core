<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Bundle\Core\EventListener;

use Ibexa\Bundle\Core\EventListener\SessionInitByPostListener;
use Ibexa\Core\MVC\Symfony\Event\PostSiteAccessMatchEvent;
use Ibexa\Core\MVC\Symfony\MVCEvents;
use Ibexa\Core\MVC\Symfony\SiteAccess;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\HttpKernel\HttpKernelInterface;

final class SessionInitByPostListenerTest extends TestCase
{
    private SessionInitByPostListener $listener;

    protected function setUp(): void
    {
        parent::setUp();
        $this->listener = new SessionInitByPostListener();
    }

    public function testGetSubscribedEvents(): void
    {
        self::assertSame(
            [
                MVCEvents::SITEACCESS => ['onSiteAccessMatch', 249],
            ],
            SessionInitByPostListener::getSubscribedEvents()
        );
    }

    public function testOnSiteAccessMatchNoSessionService(): void
    {
        $request = new Request();
        $request->setSession(new Session(new MockArraySessionStorage()));

        $event = new PostSiteAccessMatchEvent(new SiteAccess('test'), $request, HttpKernelInterface::MAIN_REQUEST);
        $listener = new SessionInitByPostListener();
        self::assertNull($listener->onSiteAccessMatch($event));
    }

    public function testOnSiteAccessMatchSubRequest(): void
    {
        $session = $this->createMock(SessionInterface::class);
        $session
            ->expects(self::never())
            ->method('getName');

        $request = new Request();
        $request->setSession($session);

        $event = new PostSiteAccessMatchEvent(new SiteAccess('test'), $request, HttpKernelInterface::SUB_REQUEST);
        $this->listener->onSiteAccessMatch($event);
    }

    public function testOnSiteAccessMatchRequestNoSessionName(): void
    {
        $sessionName = 'IBX_SESSION_ID';

        $session = $this->createMock(SessionInterface::class);
        $session
            ->method('getName')
            ->will(self::returnValue($sessionName));
        $session
            ->expects(self::once())
            ->method('isStarted')
            ->will(self::returnValue(false));
        $session
            ->expects(self::never())
            ->method('setId');
        $session
            ->expects(self::never())
            ->method('start');

        $request = new Request();
        $request->setSession($session);

        $event = new PostSiteAccessMatchEvent(new SiteAccess('test'), $request, HttpKernelInterface::MAIN_REQUEST);

        $this->listener->onSiteAccessMatch($event);
    }

    public function testOnSiteAccessMatchNewSessionName(): void
    {
        $sessionName = 'IBX_SESSION_ID';
        $sessionId = 'foobar123';
        $session = $this->createMock(SessionInterface::class);

        $session
            ->method('getName')
            ->will(self::returnValue($sessionName));
        $session
            ->expects(self::once())
            ->method('isStarted')
            ->will(self::returnValue(false));
        $session
            ->expects(self::once())
            ->method('setId')
            ->with($sessionId);
        $session
            ->expects(self::once())
            ->method('start');

        $request = new Request();
        $request->setSession($session);
        $request->request->set($sessionName, $sessionId);
        $event = new PostSiteAccessMatchEvent(new SiteAccess('test'), $request, HttpKernelInterface::MAIN_REQUEST);

        $this->listener->onSiteAccessMatch($event);
    }

    public function testOnSiteAccessMatchNoSession(): void
    {
        $request = new Request();

        $event = new PostSiteAccessMatchEvent(new SiteAccess('test'), $request, HttpKernelInterface::MAIN_REQUEST);
        $this->listener->onSiteAccessMatch($event);
    }
}
