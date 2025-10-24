<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Bundle\Core\EventListener;

use Ibexa\Bundle\Core\EventListener\RequestEventListener;
use Ibexa\Contracts\Core\SiteAccess\ConfigResolverInterface;
use Ibexa\Core\MVC\Symfony\SiteAccess;
use PHPUnit\Framework\MockObject\MockObject;
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
    /** @var MockObject */
    private $configResolver;

    /** @var MockObject */
    private $router;

    /** @var MockObject|LoggerInterface */
    private $logger;

    /** @var RequestEventListener */
    private $requestEventListener;

    /** @var Request */
    private $request;

    /** @var RequestEvent */
    private $event;

    /** @var HttpKernelInterface|MockObject */
    private $httpKernel;

    protected function setUp(): void
    {
        parent::setUp();

        $this->configResolver = $this->createMock(ConfigResolverInterface::class);
        $this->router = $this->createMock(RouterInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->requestEventListener = new RequestEventListener($this->configResolver, $this->router, 'foobar', $this->logger);

        $this->request = $this
            ->getMockBuilder(Request::class)
            ->setMethods(['getSession', 'hasSession'])
            ->getMock();

        $this->httpKernel = $this->createMock(HttpKernelInterface::class);
        $this->event = new RequestEvent(
            $this->httpKernel,
            $this->request,
            HttpKernelInterface::MAIN_REQUEST
        );
    }

    public function testSubscribedEvents()
    {
        self::assertSame(
            [
                KernelEvents::REQUEST => [
                    ['onKernelRequestForward', 10],
                    ['onKernelRequestRedirect', 0],
                ],
            ],
            $this->requestEventListener->getSubscribedEvents()
        );
    }

    public function testOnKernelRequestForwardSubRequest()
    {
        $this->httpKernel
            ->expects(self::never())
            ->method('handle');

        $event = new RequestEvent($this->httpKernel, new Request(), HttpKernelInterface::SUB_REQUEST);
        $this->requestEventListener->onKernelRequestForward($event);
    }

    public function testOnKernelRequestForward()
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

    public function testOnKernelRequestRedirectSubRequest()
    {
        $event = new RequestEvent($this->httpKernel, new Request(), HttpKernelInterface::SUB_REQUEST);
        $this->requestEventListener->onKernelRequestRedirect($event);
        self::assertFalse($event->hasResponse());
    }

    public function testOnKernelRequestRedirect()
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
        /** @var RedirectResponse $response */
        $response = $event->getResponse();
        self::assertInstanceOf(RedirectResponse::class, $response);
        self::assertSame("$semanticPathinfo?some=thing", $response->getTargetUrl());
        self::assertSame(301, $response->getStatusCode());
        self::assertTrue($event->isPropagationStopped());
    }

    public function testOnKernelRequestRedirectWithLocationId()
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
        /** @var RedirectResponse $response */
        $response = $event->getResponse();
        self::assertInstanceOf(RedirectResponse::class, $response);
        self::assertSame("$semanticPathinfo?some=thing", $response->getTargetUrl());
        self::assertSame(301, $response->getStatusCode());
        self::assertEquals(123, $response->headers->get('X-Location-Id'));
        self::assertTrue($event->isPropagationStopped());
    }

    public function testOnKernelRequestRedirectPrependSiteaccess()
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
        /** @var RedirectResponse $response */
        $response = $event->getResponse();
        self::assertInstanceOf(RedirectResponse::class, $response);
        self::assertSame("$expectedURI?some=thing", $response->getTargetUrl());
        self::assertSame(301, $response->getStatusCode());
        self::assertTrue($event->isPropagationStopped());
    }
}
