<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Bundle\Core\EventListener;

use Ibexa\Bundle\Core\EventListener\SessionSetDynamicNameListener;
use Ibexa\Contracts\Core\SiteAccess\ConfigResolverInterface;
use Ibexa\Core\MVC\Symfony\Event\PostSiteAccessMatchEvent;
use Ibexa\Core\MVC\Symfony\MVCEvents;
use Ibexa\Core\MVC\Symfony\SiteAccess;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\HttpFoundation\Session\Storage\NativeSessionStorage;
use Symfony\Component\HttpFoundation\Session\Storage\SessionStorageFactoryInterface;
use Symfony\Component\HttpFoundation\Session\Storage\SessionStorageInterface;
use Symfony\Component\HttpKernel\HttpKernelInterface;

final class SessionSetDynamicNameListenerTest extends TestCase
{
    /** @var ConfigResolverInterface&MockObject */
    private ConfigResolverInterface $configResolver;

    /** @var SessionStorageFactoryInterface&MockObject */
    private SessionStorageFactoryInterface $sessionStorageFactory;

    /** @var SessionStorageInterface&MockObject */
    private SessionStorageInterface $sessionStorage;

    protected function setUp(): void
    {
        parent::setUp();

        $this->configResolver = $this->getMockBuilder(ConfigResolverInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->sessionStorage = $this->getMockBuilder(NativeSessionStorage::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->sessionStorageFactory = $this->getMockBuilder(SessionStorageFactoryInterface::class)
            ->getMock();

        $this->sessionStorageFactory->method('createStorage')
            ->willReturn($this->sessionStorage);
    }

    public function testGetSubscribedEvents(): void
    {
        $listener = new SessionSetDynamicNameListener($this->configResolver, $this->sessionStorageFactory);

        self::assertSame(
            [
                MVCEvents::SITEACCESS => ['onSiteAccessMatch', 250],
            ],
            $listener->getSubscribedEvents()
        );
    }

    public function testOnSiteAccessMatchNoSession(): void
    {
        $request = new Request();

        $this->sessionStorage
            ->expects(self::never())
            ->method('setOptions');

        $listener = new SessionSetDynamicNameListener($this->configResolver, $this->sessionStorageFactory);
        $listener->onSiteAccessMatch(new PostSiteAccessMatchEvent(new SiteAccess('test'), $request, HttpKernelInterface::MAIN_REQUEST));
    }

    public function testOnSiteAccessMatchSubRequest(): void
    {
        $this->sessionStorage
            ->expects(self::never())
            ->method('setOptions');
        $listener = new SessionSetDynamicNameListener($this->configResolver, $this->sessionStorageFactory);
        $listener->onSiteAccessMatch(new PostSiteAccessMatchEvent(new SiteAccess('test'), new Request(), HttpKernelInterface::SUB_REQUEST));
    }

    public function testOnSiteAccessMatchNonNativeSessionStorage(): void
    {
        $this->configResolver
            ->expects(self::never())
            ->method('getParameter');
        $listener = new SessionSetDynamicNameListener(
            $this->configResolver,
            $this->createMock(SessionStorageFactoryInterface::class)
        );
        $listener->onSiteAccessMatch(new PostSiteAccessMatchEvent(new SiteAccess('test'), new Request(), HttpKernelInterface::SUB_REQUEST));
    }

    /**
     * @dataProvider onSiteAccessMatchProvider
     */
    public function testOnSiteAccessMatch(
        SiteAccess $siteAccess,
        $configuredSessionStorageOptions,
        array $expectedSessionStorageOptions
    ): void {
        $request = new Request();
        $request->setSession(new Session(new MockArraySessionStorage()));

        $this->sessionStorage
            ->expects(self::once())
            ->method('setOptions')
            ->with($expectedSessionStorageOptions);
        $this->configResolver
            ->expects(self::once())
            ->method('getParameter')
            ->with('session')
            ->will(self::returnValue($configuredSessionStorageOptions));

        $listener = new SessionSetDynamicNameListener($this->configResolver, $this->sessionStorageFactory);
        $listener->onSiteAccessMatch(new PostSiteAccessMatchEvent($siteAccess, $request, HttpKernelInterface::MAIN_REQUEST));
    }

    /**
     * @return array{array{SiteAccess, array<string, string>, array<string, string>}}
     */
    public function onSiteAccessMatchProvider(): array
    {
        return [
            [new SiteAccess('foo'), ['name' => 'IBX_SESSION_ID'], ['name' => 'IBX_SESSION_ID']],
            [new SiteAccess('foo'), ['name' => 'IBX_SESSION_ID{siteaccess_hash}'], ['name' => 'IBX_SESSION_ID' . md5('foo')]],
            [new SiteAccess('foo'), ['name' => 'this_is_a_session_name'], ['name' => 'IBX_SESSION_ID_this_is_a_session_name']],
            [new SiteAccess('foo'), ['name' => 'something{siteaccess_hash}'], ['name' => 'IBX_SESSION_ID_something' . md5('foo')]],
            [new SiteAccess('bar_baz'), ['name' => '{siteaccess_hash}something'], ['name' => 'IBX_SESSION_ID_' . md5('bar_baz') . 'something']],
            [
                new SiteAccess('foo'),
                [
                    'name' => 'this_is_a_session_name',
                    'cookie_path' => '/foo',
                    'cookie_domain' => 'foo.com',
                    'cookie_lifetime' => 86400,
                    'cookie_secure' => false,
                    'cookie_httponly' => true,
                ],
                [
                    'name' => 'IBX_SESSION_ID_this_is_a_session_name',
                    'cookie_path' => '/foo',
                    'cookie_domain' => 'foo.com',
                    'cookie_lifetime' => 86400,
                    'cookie_secure' => false,
                    'cookie_httponly' => true,
                ],
            ],
        ];
    }

    public function testOnSiteAccessMatchNoConfiguredSessionName(): void
    {
        $request = new Request();
        $request->setSession(new Session(new MockArraySessionStorage('some_default_name')));

        $configuredSessionStorageOptions = ['cookie_path' => '/bar'];
        $sessionName = 'some_default_name';
        $sessionOptions = $configuredSessionStorageOptions + ['name' => "IBX_SESSION_ID_$sessionName"];

        $this->sessionStorage
            ->expects(self::once())
            ->method('setOptions')
            ->with($sessionOptions);
        $this->configResolver
            ->expects(self::once())
            ->method('getParameter')
            ->with('session')
            ->will(self::returnValue($configuredSessionStorageOptions));

        $listener = new SessionSetDynamicNameListener($this->configResolver, $this->sessionStorageFactory);
        $listener->onSiteAccessMatch(new PostSiteAccessMatchEvent(new SiteAccess('test'), $request, HttpKernelInterface::MAIN_REQUEST));
    }
}
