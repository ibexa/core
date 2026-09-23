<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Bundle\Core\EventListener;

use Ibexa\Bundle\Core\EventListener\RequestEventListener;
use Ibexa\Contracts\Core\SiteAccess\ConfigResolverInterface;
use Ibexa\Core\MVC\Symfony\SiteAccess;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\PhpUnit\ClockMock;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\RouterInterface;

class RequestEventListenerTest extends TestCase
{
    private ConfigResolverInterface&Stub $configResolver;

    private RouterInterface&Stub $router;

    private LoggerInterface&Stub $logger;

    /** @var \Ibexa\Bundle\Core\EventListener\RequestEventListener */
    private $requestEventListener;

    /** @var \Symfony\Component\HttpFoundation\Request */
    private $request;

    /** @var \Symfony\Component\HttpKernel\Event\RequestEvent */
    private $event;

    /** @var \Symfony\Component\HttpKernel\HttpKernelInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $httpKernel;

    protected function setUp(): void
    {
        parent::setUp();

        $this->configResolver = self::createStub(ConfigResolverInterface::class);
        $this->router = self::createStub(RouterInterface::class);
        $this->logger = self::createStub(LoggerInterface::class);

        $this->requestEventListener = new RequestEventListener($this->configResolver, $this->router, 'foobar', $this->logger);

        $this->request = $this
            ->getMockBuilder(Request::class)
            ->onlyMethods(['getSession', 'hasSession'])
            ->getMock();

        $this->httpKernel = $this->createMock(HttpKernelInterface::class);
        $this->event = new RequestEvent(
            $this->httpKernel,
            $this->request,
            HttpKernelInterface::MAIN_REQUEST
        );
    }

    public function testSubscribedEvents(): void
    {
        self::assertSame(
            [
                KernelEvents::REQUEST => [
                    ['onKernelRequestForward', 10],
                    ['onKernelRequestRedirect', 0],
                ],
            ],
            RequestEventListener::getSubscribedEvents()
        );
    }

    public function testOnKernelRequestForwardSubRequest(): void
    {
        $this->httpKernel
            ->expects(self::never())
            ->method('handle');

        $event = new RequestEvent($this->httpKernel, new Request(), HttpKernelInterface::SUB_REQUEST);
        $this->requestEventListener->onKernelRequestForward($event);
    }

    public function testOnKernelRequestForward(): void
    {
        ClockMock::withClockMock(true);

        $queryParameters = ['some' => 'thing'];
        $cookieParameters = ['cookie' => 'value'];
        $request = Request::create('/test_sa/foo/bar', Request::METHOD_GET, $queryParameters, $cookieParameters);
        $semanticPathinfo = '/foo/something';
        $request->attributes->set('semanticPathinfo', $semanticPathinfo);
        $request->attributes->set('needsForward', true);
        $request->attributes->set('someAttribute', 'someValue');

        $expectedForwardRequest = Request::create($semanticPathinfo, Request::METHOD_GET, $queryParameters, $cookieParameters);
        $expectedForwardRequest->attributes->set('semanticPathinfo', $semanticPathinfo);
        $expectedForwardRequest->attributes->set('someAttribute', 'someValue');

        $response = new Response('Success!');
        $this->httpKernel
            ->expects(self::once())
            ->method('handle')
            ->with(self::equalTo($expectedForwardRequest))
            ->will(self::returnValue($response));

        $event = new RequestEvent($this->httpKernel, $request, HttpKernelInterface::MAIN_REQUEST);
        $this->requestEventListener->onKernelRequestForward($event);
        self::assertSame($response, $event->getResponse());
        self::assertTrue($event->isPropagationStopped());

        ClockMock::withClockMock(false);
    }

    public function testOnKernelRequestRedirectSubRequest(): void
    {
        $event = new RequestEvent($this->httpKernel, new Request(), HttpKernelInterface::SUB_REQUEST);
        $this->requestEventListener->onKernelRequestRedirect($event);
        self::assertFalse($event->hasResponse());
    }

    public function testOnKernelRequestRedirect(): void
    {
        $queryParameters = ['some' => 'thing'];
        $cookieParameters = ['cookie' => 'value'];
        $request = Request::create('/test_sa/foo/bar', Request::METHOD_GET, $queryParameters, $cookieParameters);
        $semanticPathinfo = '/foo/something';
        $request->attributes->set('semanticPathinfo', $semanticPathinfo);
        $request->attributes->set('needsRedirect', true);
        $request->attributes->set('siteaccess', new SiteAccess('test'));

        $event = new RequestEvent($this->httpKernel, $request, HttpKernelInterface::MAIN_REQUEST);
        $this->requestEventListener->onKernelRequestRedirect($event);
        self::assertTrue($event->hasResponse());
        /** @var \Symfony\Component\HttpFoundation\RedirectResponse $response */
        $response = $event->getResponse();
        self::assertInstanceOf(RedirectResponse::class, $response);
        self::assertSame("$semanticPathinfo?some=thing", $response->getTargetUrl());
        self::assertSame(301, $response->getStatusCode());
        self::assertTrue($event->isPropagationStopped());
    }

    public function testOnKernelRequestRedirectWithLocationId(): void
    {
        $queryParameters = ['some' => 'thing'];
        $cookieParameters = ['cookie' => 'value'];
        $request = Request::create('/test_sa/foo/bar', Request::METHOD_GET, $queryParameters, $cookieParameters);
        $semanticPathinfo = '/foo/something';
        $request->attributes->set('semanticPathinfo', $semanticPathinfo);
        $request->attributes->set('needsRedirect', true);
        $request->attributes->set('locationId', 123);
        $request->attributes->set('siteaccess', new SiteAccess('test'));

        $event = new RequestEvent($this->httpKernel, $request, HttpKernelInterface::MAIN_REQUEST);
        $this->requestEventListener->onKernelRequestRedirect($event);
        self::assertTrue($event->hasResponse());
        /** @var \Symfony\Component\HttpFoundation\RedirectResponse $response */
        $response = $event->getResponse();
        self::assertInstanceOf(RedirectResponse::class, $response);
        self::assertSame("$semanticPathinfo?some=thing", $response->getTargetUrl());
        self::assertSame(301, $response->getStatusCode());
        self::assertEquals(123, $response->headers->get('X-Location-Id'));
        self::assertTrue($event->isPropagationStopped());
    }

    public function testOnKernelRequestRedirectPrependSiteaccess(): void
    {
        $queryParameters = ['some' => 'thing'];
        $cookieParameters = ['cookie' => 'value'];
        $siteaccessMatcher = $this->createMock(SiteAccess\URILexer::class);
        $siteaccess = new SiteAccess('test', 'foo', $siteaccessMatcher);
        $semanticPathinfo = '/foo/something';

        $request = Request::create('/test_sa/foo/bar', Request::METHOD_GET, $queryParameters, $cookieParameters);
        $request->attributes->set('semanticPathinfo', $semanticPathinfo);
        $request->attributes->set('needsRedirect', true);
        $request->attributes->set('siteaccess', $siteaccess);
        $request->attributes->set('prependSiteaccessOnRedirect', true);

        $expectedURI = "/test$semanticPathinfo";
        $siteaccessMatcher
            ->expects(self::once())
            ->method('analyseLink')
            ->with($semanticPathinfo)
            ->will(self::returnValue($expectedURI));

        $event = new RequestEvent($this->httpKernel, $request, HttpKernelInterface::MAIN_REQUEST);
        $this->requestEventListener->onKernelRequestRedirect($event);
        self::assertTrue($event->hasResponse());
        /** @var \Symfony\Component\HttpFoundation\RedirectResponse $response */
        $response = $event->getResponse();
        self::assertInstanceOf(RedirectResponse::class, $response);
        self::assertSame("$expectedURI?some=thing", $response->getTargetUrl());
        self::assertSame(301, $response->getStatusCode());
        self::assertTrue($event->isPropagationStopped());
    }
}
