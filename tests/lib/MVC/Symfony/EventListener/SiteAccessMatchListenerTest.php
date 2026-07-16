<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Core\MVC\Symfony\EventListener;

use Ibexa\Bundle\Core\SiteAccess\SiteAccessMatcherRegistryInterface;
use Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException;
use Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException;
use Ibexa\Core\MVC\Symfony\Component\Serializer\CompoundMatcherNormalizer;
use Ibexa\Core\MVC\Symfony\Component\Serializer\HostElementNormalizer;
use Ibexa\Core\MVC\Symfony\Component\Serializer\HostTextNormalizer;
use Ibexa\Core\MVC\Symfony\Component\Serializer\MapNormalizer;
use Ibexa\Core\MVC\Symfony\Component\Serializer\MatcherDenormalizer;
use Ibexa\Core\MVC\Symfony\Component\Serializer\RegexNormalizer;
use Ibexa\Core\MVC\Symfony\Component\Serializer\SimplifiedRequestNormalizer;
use Ibexa\Core\MVC\Symfony\Component\Serializer\SiteAccessNormalizer;
use Ibexa\Core\MVC\Symfony\Component\Serializer\URIElementNormalizer;
use Ibexa\Core\MVC\Symfony\Component\Serializer\URITextNormalizer;
use Ibexa\Core\MVC\Symfony\Event\PostSiteAccessMatchEvent;
use Ibexa\Core\MVC\Symfony\EventListener\SiteAccessMatchListener;
use Ibexa\Core\MVC\Symfony\MVCEvents;
use Ibexa\Core\MVC\Symfony\Routing\SimplifiedRequest;
use Ibexa\Core\MVC\Symfony\SiteAccess;
use Ibexa\Core\MVC\Symfony\SiteAccess\Matcher;
use Ibexa\Core\MVC\Symfony\SiteAccess\Router;
use Ibexa\Core\MVC\Symfony\SiteAccessGroup;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\ArrayDenormalizer;
use Symfony\Component\Serializer\Normalizer\JsonSerializableNormalizer;
use Symfony\Component\Serializer\Normalizer\PropertyNormalizer;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @covers \Ibexa\Core\MVC\Symfony\EventListener\SiteAccessMatchListener
 */
final class SiteAccessMatchListenerTest extends TestCase
{
    /** @var MockObject&Router */
    private Router $saRouter;

    /** @var MockObject&EventDispatcherInterface */
    private EventDispatcherInterface $eventDispatcher;

    /** @var MockObject&SiteAccessMatcherRegistryInterface */
    private SiteAccessMatcherRegistryInterface $registry;

    private SiteAccessMatchListener $listener;

    protected function setUp(): void
    {
        parent::setUp();
        $this->saRouter = $this->createMock(Router::class);
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->registry = $this->createMock(SiteAccessMatcherRegistryInterface::class);
        $this->listener = new SiteAccessMatchListener(
            $this->saRouter,
            $this->eventDispatcher,
            $this->getSerializer()
        );
    }

    public function testGetSubscribedEvents(): void
    {
        self::assertSame(
            [KernelEvents::REQUEST => ['onKernelRequest', 45]],
            SiteAccessMatchListener::getSubscribedEvents()
        );
    }

    /**
     * @param SiteAccessGroup[] $groups
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
     * @throws InvalidArgumentException
     * @throws NotFoundException
     */
    public function createAndDispatchRequest(SiteAccess $siteAccess): Request
    {
        $request = new Request();
        $request->attributes->set('serialized_siteaccess', $this->serializeSiteAccess($siteAccess));

        $request->attributes->set('serialized_siteaccess_matcher', $this->serializeMatcher($siteAccess));
        if ($siteAccess->matcher instanceof Matcher\Compound) {
            $request->attributes->set(
                'serialized_siteaccess_sub_matchers',
                $this->serializeSubMatchers($siteAccess->matcher)
            );
        }

        $this->dispatchRequestEvent($request, $siteAccess);
        self::assertEquals($siteAccess, $request->attributes->get('siteaccess'));

        return $request;
    }

    /**
     * @throws NotFoundException
     * @throws InvalidArgumentException
     */
    public function testOnKernelRequestSerializedSA(): void
    {
        $matcher = new Matcher\URIElement(1);
        $siteAccess = $this->createSiteAccess($matcher, null, [new SiteAccessGroup('test_group')]);
        $request = $this->createAndDispatchRequest($siteAccess);

        self::assertFalse($request->attributes->has('serialized_siteaccess'));
    }

    /**
     * @throws NotFoundException
     * @throws InvalidArgumentException
     */
    public function testOnKernelRequestSerializedSAWithCompoundMatcher(): void
    {
        $compoundMatcher = new Matcher\Compound\LogicalAnd([]);
        $subMatchers = [
            Matcher\Map\URI::class => new Matcher\Map\URI([]),
            Matcher\Map\Host::class => new Matcher\Map\Host([]),
        ];
        $compoundMatcher->setSubMatchers($subMatchers);
        $siteAccess = new SiteAccess(
            'test',
            'matching_type',
            $compoundMatcher
        );
        $request = $this->createAndDispatchRequest($siteAccess);
        self::assertFalse($request->attributes->has('serialized_siteaccess'));
    }

    /**
     * @throws NotFoundException
     * @throws InvalidArgumentException
     */
    public function testOnKernelRequestSerializedSAWithMatcherInMatcherRegistry(): void
    {
        $matcher = new TestMatcher([]);

        $matcher2 = new TestMatcher([]);
        $matcher2->setMapKey('key_foobar');

        $this->registry
            ->expects(self::once())
            ->method('hasMatcher')
            ->with(TestMatcher::class)
            ->willReturn(true);

        $this->registry
            ->expects(self::once())
            ->method('getMatcher')
            ->with(TestMatcher::class)
            ->willReturn($matcher);

        $siteAccess = $this->createSiteAccess(
            $matcher2,
            null,
            [new SiteAccessGroup('test_group')]
        );

        $request = $this->createAndDispatchRequest($siteAccess);
        /** @var TestMatcher $siteAccessMatcher */
        $siteAccessMatcher = $siteAccess->matcher;
        self::assertEquals('key_foobar', $siteAccessMatcher->getMapKey());
        self::assertFalse($request->attributes->has('serialized_siteaccess'));
    }

    /**
     * @throws NotFoundException
     * @throws InvalidArgumentException
     */
    public function testOnKernelRequestSiteAccessPresent(): void
    {
        $siteAccess = new SiteAccess('test');
        $request = new Request();
        $request->attributes->set('siteaccess', $siteAccess);
        $this->dispatchRequestEvent($request, $siteAccess);
        self::assertSame($siteAccess, $request->attributes->get('siteaccess'));
    }

    /**
     * @throws InvalidArgumentException
     * @throws NotFoundException
     */
    public function testOnKernelRequest(): void
    {
        $siteAccess = new SiteAccess('test');
        $scheme = 'https';
        $host = 'phoenix-rises.fm';
        $port = 1234;
        $path = '/foo/bar';
        $request = Request::create(sprintf('%s://%s:%d%s', $scheme, $host, $port, $path));
        $this->assertRequestHasSiteAccess($request, null, $siteAccess);
    }

    /**
     * @throws NotFoundException
     * @throws InvalidArgumentException
     */
    public function testOnKernelRequestUserHashWithOriginalRequest(): void
    {
        $siteAccess = new SiteAccess('test');
        $scheme = 'https';
        $host = 'phoenix-rises.fm';
        $port = 1234;
        $path = '/foo/bar';
        $originalRequest = Request::create(sprintf('%s://%s:%d%s', $scheme, $host, $port, $path));
        $request = Request::create('http://localhost/_fos_user_hash');
        $request->attributes->set('_ez_original_request', $originalRequest);
        $this->assertRequestHasSiteAccess($request, $originalRequest, $siteAccess);
    }

    private function serializeSiteAccess(SiteAccess $siteAccess): string
    {
        return $this->getSerializer()->serialize($siteAccess, 'json');
    }

    /**
     * @throws InvalidArgumentException
     * @throws NotFoundException
     */
    private function assertRequestHasSiteAccess(
        Request $request,
        ?Request $originalRequest,
        SiteAccess $siteAccess
    ): void {
        $originalRequest ??= $request;
        $event = new RequestEvent(
            $this->createMock(HttpKernelInterface::class),
            $request,
            HttpKernelInterface::MAIN_REQUEST
        );

        $simplifiedRequest = new SimplifiedRequest(
            $originalRequest->getScheme(),
            $originalRequest->getHost(),
            $originalRequest->getPort(),
            $originalRequest->getPathInfo(),
            $originalRequest->query->all(),
            $originalRequest->getLanguages(),
            $originalRequest->headers->all(),
        );

        $this->saRouter
            ->expects(self::once())
            ->method('match')
            ->with($simplifiedRequest)
            ->willReturn($siteAccess)
        ;

        $postSAMatchEvent = new PostSiteAccessMatchEvent($siteAccess, $request, $event->getRequestType());
        $this->eventDispatcher
            ->expects(self::once())
            ->method('dispatch')
            ->with(self::equalTo($postSAMatchEvent), MVCEvents::SITEACCESS)
        ;

        $this->listener->onKernelRequest($event);
        self::assertSame($siteAccess, $request->attributes->get('siteaccess'));
    }

    /**
     * @throws InvalidArgumentException
     * @throws NotFoundException
     */
    private function dispatchRequestEvent(
        Request $request,
        SiteAccess $siteAccess
    ): void {
        $event = new RequestEvent(
            $this->createMock(HttpKernelInterface::class),
            $request,
            HttpKernelInterface::MAIN_REQUEST
        );

        $this->saRouter
            ->expects(self::never())
            ->method('match');

        $postSAMatchEvent = new PostSiteAccessMatchEvent($siteAccess, $request, $event->getRequestType());
        $this->eventDispatcher
            ->expects(self::once())
            ->method('dispatch')
            ->with($postSAMatchEvent, MVCEvents::SITEACCESS);

        $this->listener->onKernelRequest($event);
    }

    private function getSerializer(): SerializerInterface
    {
        return new Serializer(
            [
                new ArrayDenormalizer(),
                new SiteAccessNormalizer(),
                new MatcherDenormalizer($this->registry),
                new CompoundMatcherNormalizer(),
                new MapNormalizer(),
                new HostElementNormalizer(),
                new URITextNormalizer(),
                new HostTextNormalizer(),
                new RegexNormalizer(),
                new URIElementNormalizer(),
                new SimplifiedRequestNormalizer(),
                new JsonSerializableNormalizer(),
                new PropertyNormalizer(),
            ],
            [new JsonEncoder()]
        );
    }

    private function serializeMatcher(SiteAccess $siteAccess): string
    {
        return $this->getSerializer()->serialize(
            $siteAccess->matcher,
            'json',
            [AbstractNormalizer::IGNORED_ATTRIBUTES => ['request', 'container', 'matcherBuilder']]
        );
    }

    /**
     * @phpstan-return array<class-string<Matcher>, string>
     */
    private function serializeSubMatchers(Matcher\Compound $compoundMatcher): array
    {
        $serializedSubMatchers = [];
        foreach ($compoundMatcher->getSubMatchers() as $subMatcher) {
            $serializedSubMatchers[get_class($subMatcher)] = $this->getSerializer()->serialize(
                $subMatcher,
                'json',
                [AbstractNormalizer::IGNORED_ATTRIBUTES => ['request', 'container', 'matcherBuilder']]
            );
        }

        return $serializedSubMatchers;
    }
}
