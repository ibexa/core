<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Core\MVC\Symfony\EventListener;

use Ibexa\Core\MVC\Symfony\Event\PostSiteAccessMatchEvent;
use Ibexa\Core\MVC\Symfony\EventListener\SiteAccessRestoreListener;
use Ibexa\Core\MVC\Symfony\MVCEvents;
use Ibexa\Core\MVC\Symfony\SiteAccess;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Event\FinishRequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * @covers \Ibexa\Core\MVC\Symfony\EventListener\SiteAccessRestoreListener
 */
final class SiteAccessRestoreListenerTest extends TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject&\Symfony\Component\HttpKernel\HttpKernelInterface */
    private HttpKernelInterface $kernel;

    /** @var \PHPUnit\Framework\MockObject\MockObject&\Symfony\Component\EventDispatcher\EventDispatcherInterface */
    private EventDispatcherInterface $eventDispatcher;

    private RequestStack $requestStack;

    private SiteAccessRestoreListener $listener;

    protected function setUp(): void
    {
        parent::setUp();
        $this->kernel = $this->createMock(HttpKernelInterface::class);
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->requestStack = new RequestStack();
        $this->listener = new SiteAccessRestoreListener(
            $this->requestStack,
            $this->eventDispatcher
        );
    }

    public function testGetSubscribedEvents(): void
    {
        self::assertSame(
            [KernelEvents::FINISH_REQUEST => ['onKernelFinishRequest', 0]],
            SiteAccessRestoreListener::getSubscribedEvents()
        );
    }

    public function testRestoresParentSiteAccessWhenSubRequestFinishes(): void
    {
        $parentSiteAccess = new SiteAccess('site_fr', 'uri:element');
        $parentRequest = self::createRequestWithSiteAccess($parentSiteAccess);
        $subRequest = self::createRequestWithSiteAccess(new SiteAccess('admin', 'uri:element'));

        $this->requestStack->push($parentRequest);
        $this->requestStack->push($subRequest);

        $this->eventDispatcher
            ->expects(self::once())
            ->method('dispatch')
            ->with(
                self::callback(
                    static function (PostSiteAccessMatchEvent $event) use ($parentSiteAccess, $parentRequest): bool {
                        return $event->getSiteAccess() === $parentSiteAccess
                            && $event->getRequest() === $parentRequest
                            && $event->getRequestType() === HttpKernelInterface::SUB_REQUEST;
                    }
                ),
                MVCEvents::SITEACCESS
            );

        $this->listener->onKernelFinishRequest(
            new FinishRequestEvent($this->kernel, $subRequest, HttpKernelInterface::SUB_REQUEST)
        );
    }

    /**
     * @dataProvider provideNoDispatchCases
     *
     * @param \Symfony\Component\HttpFoundation\Request[] $requests
     */
    public function testNoDispatch(array $requests, int $requestType): void
    {
        $finishingRequest = null;
        foreach ($requests as $request) {
            $this->requestStack->push($request);
            $finishingRequest = $request;
        }
        self::assertNotNull($finishingRequest);

        $this->eventDispatcher->expects(self::never())->method('dispatch');

        $this->listener->onKernelFinishRequest(
            new FinishRequestEvent($this->kernel, $finishingRequest, $requestType)
        );
    }

    /**
     * @return iterable<string, array{\Symfony\Component\HttpFoundation\Request[], int}>
     */
    public static function provideNoDispatchCases(): iterable
    {
        yield 'main request' => [
            [self::createRequestWithSiteAccess(new SiteAccess('site'))],
            HttpKernelInterface::MAIN_REQUEST,
        ];

        yield 'sub-request without parent request' => [
            [Request::create('/_fragment')],
            HttpKernelInterface::SUB_REQUEST,
        ];

        yield 'parent request without siteaccess attribute' => [
            [Request::create('/'), Request::create('/_fragment')],
            HttpKernelInterface::SUB_REQUEST,
        ];
    }

    private static function createRequestWithSiteAccess(SiteAccess $siteAccess): Request
    {
        $request = Request::create('/');
        $request->attributes->set('siteaccess', $siteAccess);

        return $request;
    }
}
