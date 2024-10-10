<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
namespace Ibexa\Tests\Core\MVC\Symfony\EventListener;

use Ibexa\Bundle\Core\SiteAccess\SiteAccessMatcherRegistryInterface;
use Ibexa\Core\MVC\Symfony\Component\Serializer\SerializerTrait;
use Ibexa\Core\MVC\Symfony\Event\PostSiteAccessMatchEvent;
use Ibexa\Core\MVC\Symfony\EventListener\SiteAccessMatchListener;
use Ibexa\Core\MVC\Symfony\MVCEvents;
use Ibexa\Core\MVC\Symfony\Routing\SimplifiedRequest;
use Ibexa\Core\MVC\Symfony\SiteAccess;
use Ibexa\Core\MVC\Symfony\SiteAccess\Matcher;
use Ibexa\Core\MVC\Symfony\SiteAccess\Router;
use Ibexa\Core\MVC\Symfony\SiteAccessGroup;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;

class SiteAccessMatchListenerTest extends TestCase
{
    use SerializerTrait;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    private $saRouter;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    private $eventDispatcher;

    /** @var \Ibexa\Core\MVC\Symfony\EventListener\SiteAccessMatchListener */
    private $listener;

    protected function setUp(): void
    {
        parent::setUp();
        $this->saRouter = $this->createMock(Router::class);
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $matcherRegistryMock = $this->createMock(SiteAccessMatcherRegistryInterface::class);
        $matcherRegistryMock->method('hasMatcher')->willReturn(false);
        $this->listener = new SiteAccessMatchListener(
            $this->saRouter,
            $this->eventDispatcher,
            $matcherRegistryMock
        );
    }

    public function testGetSubscribedEvents()
    {
        $this->assertSame(
            [KernelEvents::REQUEST => ['onKernelRequest', 45]],
            SiteAccessMatchListener::getSubscribedEvents()
        );
    }

    /**
     * @param \Ibexa\Core\MVC\Symfony\SiteAccessGroup[] $groups
     */
    protected function createSiteAccess(
        ?Matcher $matcher = null,
        ?string $provider = null,
        array $groups = []
    ): SiteAccess {
        return new SiteAccess(
            'test',
            'matching_type',
            $matcher,
            $provider,
            $groups
        );
    }

    /**
     * @param \Ibexa\Core\MVC\Symfony\SiteAccess $siteAccess
     *
     * @return \Symfony\Component\HttpFoundation\Request
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException
     */
    public function createRequest(SiteAccess $siteAccess): Request
    {
        $request = new Request();
        $request->attributes->set('serialized_siteaccess', json_encode($siteAccess));
        $request->attributes->set(
            'serialized_siteaccess_matcher',
            $this->getSerializer()->serialize(
                $siteAccess->matcher,
                'json',
                [AbstractNormalizer::IGNORED_ATTRIBUTES => ['request', 'container', 'matcherBuilder']]
            )
        );
        $event = new RequestEvent(
            $this->createMock(HttpKernelInterface::class),
            $request,
            HttpKernelInterface::MASTER_REQUEST
        );

        $this->saRouter
            ->expects($this->never())
            ->method('match');

        $postSAMatchEvent = new PostSiteAccessMatchEvent($siteAccess, $request, $event->getRequestType());
        $this->eventDispatcher
            ->expects($this->once())
            ->method('dispatch')
            ->with($this->equalTo($postSAMatchEvent), MVCEvents::SITEACCESS);

        $this->listener->onKernelRequest($event);
        $this->assertEquals($siteAccess, $request->attributes->get('siteaccess'));

        return $request;
    }

    public function testOnKernelRequestSerializedSA()
    {
        $matcher = new SiteAccess\Matcher\URIElement(1);
        $siteAccess = $this->createSiteAccess($matcher, null, [new SiteAccessGroup('test_group')]);
        $request = $this->createRequest($siteAccess);
        $this->assertFalse($request->attributes->has('serialized_siteaccess'));
    }

    public function testOnKernelRequestSerializedSAWithCompoundMatcher()
    {
        $compoundMatcher = new SiteAccess\Matcher\Compound\LogicalAnd([]);
        $subMatchers = [
            SiteAccess\Matcher\Map\URI::class => new SiteAccess\Matcher\Map\URI([]),
            SiteAccess\Matcher\Map\Host::class => new SiteAccess\Matcher\Map\Host([]),
        ];
        $compoundMatcher->setSubMatchers($subMatchers);
        $siteAccess = new SiteAccess(
            'test',
            'matching_type',
            $compoundMatcher
        );
        $request = new Request();
        $request->attributes->set('serialized_siteaccess', json_encode($siteAccess));
        $request->attributes->set(
            'serialized_siteaccess_matcher',
            $this->getSerializer()->serialize(
                $siteAccess->matcher,
                'json',
                [AbstractNormalizer::IGNORED_ATTRIBUTES => ['request', 'container', 'matcherBuilder']]
            )
        );
        $serializedSubMatchers = [];
        foreach ($subMatchers as $subMatcher) {
            $serializedSubMatchers[get_class($subMatcher)] = $this->getSerializer()->serialize(
                $subMatcher,
                'json',
                [AbstractNormalizer::IGNORED_ATTRIBUTES => ['request', 'container', 'matcherBuilder']]
            );
        }
        $request->attributes->set(
            'serialized_siteaccess_sub_matchers',
            $serializedSubMatchers
        );
        $event = new RequestEvent(
            $this->createMock(HttpKernelInterface::class),
            $request,
            HttpKernelInterface::MASTER_REQUEST
        );

        $this->saRouter
            ->expects($this->never())
            ->method('match');

        $postSAMatchEvent = new PostSiteAccessMatchEvent($siteAccess, $request, $event->getRequestType());
        $this->eventDispatcher
            ->expects($this->once())
            ->method('dispatch')
            ->with($this->equalTo($postSAMatchEvent), MVCEvents::SITEACCESS);

        $this->listener->onKernelRequest($event);
        $this->assertEquals($siteAccess, $request->attributes->get('siteaccess'));
        $this->assertFalse($request->attributes->has('serialized_siteaccess'));
    }

    public function testOnKernelRequestSerializedSAWithMatcherInMatcherRegistry(): void
    {
        $matcher = new CustomMatcher([]);
        $matcherRegistryMock = $this->createMock(SiteAccessMatcherRegistryInterface::class);
        $matcherRegistryMock->method('hasMatcher')->willReturn(true);
        $matcherRegistryMock->method('getMatcher')->willReturn($matcher);
        $matcher2 = new CustomMatcher([]);
        $matcher2->setMapKey('key_foobar');

        $matcherRegistryMock
            ->expects($this->once())
            ->method('getMatcher')
            ->with('Ibexa\Tests\Core\MVC\Symfony\EventListener\CustomMatcher');
        $this->listener = new SiteAccessMatchListener(
            $this->saRouter,
            $this->eventDispatcher,
            $matcherRegistryMock
        );

        $siteAccess = $this->createSiteAccess(
            $matcher2,
            null,
            [new SiteAccessGroup('test_group')]
        );

        $request = $this->createRequest($siteAccess);
        /** @var CustomMatcher $siteAccessMatcher */
        $siteAccessMatcher = $siteAccess->matcher;
        $this->assertEquals('key_foobar', $siteAccessMatcher->getMapKey());
        $this->assertFalse($request->attributes->has('serialized_siteaccess'));
    }

    public function testOnKernelRequestSiteAccessPresent()
    {
        $siteAccess = new SiteAccess('test');
        $request = new Request();
        $request->attributes->set('siteaccess', $siteAccess);
        $event = new RequestEvent(
            $this->createMock(HttpKernelInterface::class),
            $request,
            HttpKernelInterface::MASTER_REQUEST
        );

        $this->saRouter
            ->expects($this->never())
            ->method('match');

        $postSAMatchEvent = new PostSiteAccessMatchEvent($siteAccess, $request, $event->getRequestType());
        $this->eventDispatcher
            ->expects($this->once())
            ->method('dispatch')
            ->with($this->equalTo($postSAMatchEvent), MVCEvents::SITEACCESS);

        $this->listener->onKernelRequest($event);
        $this->assertSame($siteAccess, $request->attributes->get('siteaccess'));
    }

    public function testOnKernelRequest()
    {
        $siteAccess = new SiteAccess('test');
        $scheme = 'https';
        $host = 'phoenix-rises.fm';
        $port = 1234;
        $path = '/foo/bar';
        $request = Request::create(sprintf('%s://%s:%d%s', $scheme, $host, $port, $path));
        $event = new RequestEvent(
            $this->createMock(HttpKernelInterface::class),
            $request,
            HttpKernelInterface::MASTER_REQUEST
        );

        $simplifiedRequest = new SimplifiedRequest(
            [
                'scheme' => $request->getScheme(),
                'host' => $request->getHost(),
                'port' => $request->getPort(),
                'pathinfo' => $request->getPathInfo(),
                'queryParams' => $request->query->all(),
                'languages' => $request->getLanguages(),
                'headers' => $request->headers->all(),
            ]
        );

        $this->saRouter
            ->expects($this->once())
            ->method('match')
            ->with($this->equalTo($simplifiedRequest))
            ->will($this->returnValue($siteAccess));

        $postSAMatchEvent = new PostSiteAccessMatchEvent($siteAccess, $request, $event->getRequestType());
        $this->eventDispatcher
            ->expects($this->once())
            ->method('dispatch')
            ->with($this->equalTo($postSAMatchEvent), MVCEvents::SITEACCESS);

        $this->listener->onKernelRequest($event);
        $this->assertSame($siteAccess, $request->attributes->get('siteaccess'));
    }

    public function testOnKernelRequestUserHashWithOriginalRequest()
    {
        $siteAccess = new SiteAccess('test');
        $scheme = 'https';
        $host = 'phoenix-rises.fm';
        $port = 1234;
        $path = '/foo/bar';
        $originalRequest = Request::create(sprintf('%s://%s:%d%s', $scheme, $host, $port, $path));
        $request = Request::create('http://localhost/_fos_user_hash');
        $request->attributes->set('_ez_original_request', $originalRequest);
        $event = new RequestEvent(
            $this->createMock(HttpKernelInterface::class),
            $request,
            HttpKernelInterface::MASTER_REQUEST
        );

        $simplifiedRequest = new SimplifiedRequest(
            [
                'scheme' => $originalRequest->getScheme(),
                'host' => $originalRequest->getHost(),
                'port' => $originalRequest->getPort(),
                'pathinfo' => $originalRequest->getPathInfo(),
                'queryParams' => $originalRequest->query->all(),
                'languages' => $originalRequest->getLanguages(),
                'headers' => $originalRequest->headers->all(),
            ]
        );

        $this->saRouter
            ->expects($this->once())
            ->method('match')
            ->with($this->equalTo($simplifiedRequest))
            ->will($this->returnValue($siteAccess));

        $postSAMatchEvent = new PostSiteAccessMatchEvent($siteAccess, $request, $event->getRequestType());
        $this->eventDispatcher
            ->expects($this->once())
            ->method('dispatch')
            ->with($this->equalTo($postSAMatchEvent), MVCEvents::SITEACCESS);

        $this->listener->onKernelRequest($event);
        $this->assertSame($siteAccess, $request->attributes->get('siteaccess'));
    }
}

class_alias(SiteAccessMatchListenerTest::class, 'eZ\Publish\Core\MVC\Symfony\EventListener\Tests\SiteAccessMatchListenerTest');
