<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Core\MVC\Symfony\Routing;

use Ibexa\Contracts\Core\Repository\ContentService;
use Ibexa\Contracts\Core\Repository\LocationService;
use Ibexa\Contracts\Core\Repository\URLAliasService;
use Ibexa\Contracts\Core\Repository\Values\Content\ContentInfo;
use Ibexa\Contracts\Core\Repository\Values\Content\URLAlias;
use Ibexa\Core\Base\Exceptions\NotFoundException;
use Ibexa\Core\MVC\Symfony\Routing\Generator\UrlAliasGenerator;
use Ibexa\Core\MVC\Symfony\Routing\UrlAliasRouter;
use Ibexa\Core\MVC\Symfony\SiteAccess;
use Ibexa\Core\MVC\Symfony\SiteAccess\Matcher;
use Ibexa\Core\MVC\Symfony\View\Manager as ViewManager;
use Ibexa\Core\Repository\Repository;
use Ibexa\Core\Repository\Values\Content\Location;
use InvalidArgumentException;
use LogicException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use stdClass;
use Symfony\Cmf\Component\Routing\RouteObjectInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RequestContext;

class UrlAliasRouterTest extends TestCase
{
    protected Repository & MockObject $repository;

    protected URLAliasService & MockObject $urlAliasService;

    protected LocationService & MockObject $locationService;

    protected ContentService & MockObject $contentService;

    protected UrlAliasGenerator & MockObject $urlAliasGenerator;

    protected RequestContext $requestContext;

    protected UrlAliasRouter $router;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = $this->createMock(Repository::class);
        $this->urlAliasService = $this->createMock(URLAliasService::class);
        $this->locationService = $this->createMock(LocationService::class);
        $this->contentService = $this->createMock(ContentService::class);
        $this->urlAliasGenerator = $this->createMock(UrlAliasGenerator::class);
        $this->requestContext = new RequestContext();

        $this->router = $this->getRouter(
            $this->locationService,
            $this->urlAliasService,
            $this->contentService,
            $this->urlAliasGenerator,
            $this->requestContext
        );
    }

    /**
     * @param \Ibexa\Contracts\Core\Repository\LocationService $locationService
     * @param \Ibexa\Contracts\Core\Repository\URLAliasService $urlAliasService
     * @param \Ibexa\Contracts\Core\Repository\ContentService $contentService
     * @param \Ibexa\Core\MVC\Symfony\Routing\Generator\UrlAliasGenerator $urlAliasGenerator
     * @param \Symfony\Component\Routing\RequestContext $requestContext
     *
     * @return \Ibexa\Core\MVC\Symfony\Routing\UrlAliasRouter
     */
    protected function getRouter(LocationService $locationService, URLAliasService $urlAliasService, ContentService $contentService, UrlAliasGenerator $urlAliasGenerator, RequestContext $requestContext): UrlAliasRouter
    {
        return new UrlAliasRouter($locationService, $urlAliasService, $contentService, $urlAliasGenerator, $requestContext);
    }

    public function testRequestContext(): void
    {
        self::assertSame($this->requestContext, $this->router->getContext());
        $newContext = new RequestContext();
        $this->urlAliasGenerator
            ->expects(self::once())
            ->method('setRequestContext')
            ->with($newContext);
        $this->router->setContext($newContext);
        self::assertSame($newContext, $this->router->getContext());
    }

    public function testMatch(): void
    {
        $this->expectException(RuntimeException::class);

        $this->router->match('/foo');
    }

    /**
     * @dataProvider providerTestSupports
     */
    public function testSupports(Location|stdClass|string $routeReference, bool $isSupported): void
    {
        self::assertSame($isSupported, $this->router->supports($routeReference));
    }

    public function providerTestSupports(): array
    {
        return [
            [new Location(), true],
            [new stdClass(), false],
            [UrlAliasRouter::URL_ALIAS_ROUTE_NAME, true],
            ['some_route_name', false],
        ];
    }

    /**
     * @param $pathInfo
     *
     * @return \Symfony\Component\HttpFoundation\Request
     */
    protected function getRequestByPathInfo($pathInfo)
    {
        $request = Request::create($pathInfo);
        $request->attributes->set('semanticPathinfo', $pathInfo);
        $request->attributes->set(
            'siteaccess',
            new SiteAccess(
                'test',
                'fake',
                $this->createMock(Matcher::class)
            )
        );

        return $request;
    }

    public function testMatchRequestLocation(): void
    {
        $pathInfo = '/foo/bar';
        $destinationId = 123;
        $urlAlias = new URLAlias(
            [
                'path' => $pathInfo,
                'type' => URLAlias::LOCATION,
                'destination' => $destinationId,
                'isHistory' => false,
            ]
        );
        $request = $this->getRequestByPathInfo($pathInfo);
        $this->urlAliasService
            ->expects(self::once())
            ->method('lookup')
            ->with($pathInfo)
            ->will(self::returnValue($urlAlias));
        $this->urlAliasGenerator
            ->expects(self::once())
            ->method('loadLocation')
            ->will(self::returnValue(new Location(['contentInfo' => new ContentInfo(['id' => 456])])));

        $expected = [
            '_route' => UrlAliasRouter::URL_ALIAS_ROUTE_NAME,
            '_controller' => UrlAliasRouter::VIEW_ACTION,
            'locationId' => $destinationId,
            'contentId' => 456,
            'viewType' => ViewManager::VIEW_TYPE_FULL,
            'layout' => true,
        ];
        self::assertEquals($expected, $this->router->matchRequest($request));
    }

    public function testMatchRequestLocationWithCaseRedirect(): void
    {
        $pathInfo = '/Foo/bAR';
        $urlAliasPath = '/foo/bar';
        $destinationId = 123;
        $urlAlias = new URLAlias(
            [
                'path' => $urlAliasPath,
                'type' => URLAlias::LOCATION,
                'destination' => $destinationId,
                'isHistory' => false,
            ]
        );
        $request = $this->getRequestByPathInfo($pathInfo);
        $this->urlAliasGenerator
            ->expects(self::once())
            ->method('isUriPrefixExcluded')
            ->with($pathInfo)
            ->will(self::returnValue(false));
        $this->urlAliasService
            ->expects(self::once())
            ->method('lookup')
            ->with($pathInfo)
            ->will(self::returnValue($urlAlias));
        $this->urlAliasGenerator
            ->expects(self::once())
            ->method('loadLocation')
            ->will(self::returnValue(new Location(['contentInfo' => new ContentInfo(['id' => 456])])));

        $expected = [
            '_route' => UrlAliasRouter::URL_ALIAS_ROUTE_NAME,
            '_controller' => UrlAliasRouter::VIEW_ACTION,
            'locationId' => $destinationId,
            'contentId' => 456,
            'viewType' => ViewManager::VIEW_TYPE_FULL,
            'layout' => true,
            'needsRedirect' => true,
            'semanticPathinfo' => $urlAliasPath,
        ];
        self::assertEquals($expected, $this->router->matchRequest($request));
    }

    public function testMatchRequestLocationWrongCaseUriPrefixExcluded(): void
    {
        $pathInfo = '/Foo/bAR';
        $urlAliasPath = '/foo/bar';
        $destinationId = 123;
        $urlAlias = new URLAlias(
            [
                'path' => $urlAliasPath,
                'type' => URLAlias::LOCATION,
                'destination' => $destinationId,
                'isHistory' => false,
            ]
        );
        $request = $this->getRequestByPathInfo($pathInfo);
        $this->urlAliasGenerator
            ->expects(self::once())
            ->method('isUriPrefixExcluded')
            ->with($pathInfo)
            ->will(self::returnValue(true));
        $this->urlAliasService
            ->expects(self::once())
            ->method('lookup')
            ->with($pathInfo)
            ->will(self::returnValue($urlAlias));
        $this->urlAliasGenerator
            ->expects(self::once())
            ->method('loadLocation')
            ->will(self::returnValue(new Location(['contentInfo' => new ContentInfo(['id' => 456])])));

        $expected = [
            '_route' => UrlAliasRouter::URL_ALIAS_ROUTE_NAME,
            '_controller' => UrlAliasRouter::VIEW_ACTION,
            'contentId' => 456,
            'locationId' => $destinationId,
            'viewType' => ViewManager::VIEW_TYPE_FULL,
            'layout' => true,
            'needsRedirect' => true,
            'semanticPathinfo' => $urlAliasPath,
        ];
        self::assertEquals($expected, $this->router->matchRequest($request));
    }

    public function testMatchRequestLocationCorrectCaseUriPrefixExcluded(): void
    {
        $pathInfo = $urlAliasPath = '/foo/bar';
        $destinationId = 123;
        $urlAlias = new URLAlias(
            [
                'path' => $urlAliasPath,
                'type' => URLAlias::LOCATION,
                'destination' => $destinationId,
                'isHistory' => false,
            ]
        );
        $request = $this->getRequestByPathInfo($pathInfo);
        $this->urlAliasGenerator
            ->expects(self::once())
            ->method('isUriPrefixExcluded')
            ->with($pathInfo)
            ->will(self::returnValue(true));
        $this->urlAliasService
            ->expects(self::once())
            ->method('lookup')
            ->with($pathInfo)
            ->will(self::returnValue($urlAlias));
        $this->urlAliasGenerator
            ->expects(self::once())
            ->method('loadLocation')
            ->will(self::returnValue(new Location(['contentInfo' => new ContentInfo(['id' => 456])])));

        $expected = [
            '_route' => UrlAliasRouter::URL_ALIAS_ROUTE_NAME,
            '_controller' => UrlAliasRouter::VIEW_ACTION,
            'locationId' => $destinationId,
            'contentId' => 456,
            'viewType' => ViewManager::VIEW_TYPE_FULL,
            'layout' => true,
        ];
        self::assertEquals($expected, $this->router->matchRequest($request));
        self::assertFalse($request->attributes->has('needsRedirect'));
        self::assertSame($pathInfo, $request->attributes->get('semanticPathinfo'));
    }

    public function testMatchRequestLocationHistory(): void
    {
        $pathInfo = '/foo/bar';
        $newPathInfo = '/foo/bar-new';
        $destinationId = 123;
        $destinationLocation = new Location([
            'id' => $destinationId,
            'contentInfo' => new ContentInfo(['id' => 456]),
        ]);
        $urlAlias = new URLAlias(
            [
                'path' => $pathInfo,
                'type' => URLAlias::LOCATION,
                'destination' => $destinationId,
                'isHistory' => true,
            ]
        );
        $request = $this->getRequestByPathInfo($pathInfo);
        $this->urlAliasService
            ->expects(self::once())
            ->method('lookup')
            ->with($pathInfo)
            ->will(self::returnValue($urlAlias));
        $this->urlAliasGenerator
            ->expects(self::once())
            ->method('generate')
            ->with($destinationLocation)
            ->will(self::returnValue($newPathInfo));
        $this->urlAliasGenerator
            ->expects(self::once())
            ->method('loadLocation')
            ->will(self::returnValue($destinationLocation));

        $expected = [
            '_route' => UrlAliasRouter::URL_ALIAS_ROUTE_NAME,
            '_controller' => UrlAliasRouter::VIEW_ACTION,
            'locationId' => $destinationId,
            'contentId' => 456,
            'viewType' => ViewManager::VIEW_TYPE_FULL,
            'layout' => true,
            'needsRedirect' => true,
            'semanticPathinfo' => $newPathInfo,
            'prependSiteaccessOnRedirect' => false,
        ];
        self::assertEquals($expected, $this->router->matchRequest($request));
    }

    public function testMatchRequestLocationCustom(): void
    {
        $pathInfo = '/foo/bar';
        $destinationId = 123;
        $urlAlias = new URLAlias(
            [
                'path' => $pathInfo,
                'type' => URLAlias::LOCATION,
                'destination' => $destinationId,
                'isHistory' => false,
                'isCustom' => true,
                'forward' => false,
            ]
        );
        $request = $this->getRequestByPathInfo($pathInfo);
        $this->urlAliasService
            ->expects(self::once())
            ->method('lookup')
            ->with($pathInfo)
            ->will(self::returnValue($urlAlias));
        $this->urlAliasGenerator
            ->expects(self::once())
            ->method('loadLocation')
            ->will(self::returnValue(new Location(['contentInfo' => new ContentInfo(['id' => 456])])));

        $expected = [
            '_route' => UrlAliasRouter::URL_ALIAS_ROUTE_NAME,
            '_controller' => UrlAliasRouter::VIEW_ACTION,
            'locationId' => $destinationId,
            'contentId' => 456,
            'viewType' => ViewManager::VIEW_TYPE_FULL,
            'layout' => true,
        ];
        self::assertEquals($expected, $this->router->matchRequest($request));
    }

    public function testMatchRequestLocationCustomForward(): void
    {
        $pathInfo = '/foo/bar';
        $newPathInfo = '/foo/bar-new';
        $destinationId = 123;
        $destinationLocation = new Location([
            'id' => $destinationId,
            'contentInfo' => new ContentInfo(['id' => 456]),
        ]);
        $urlAlias = new URLAlias(
            [
                'path' => $pathInfo,
                'type' => URLAlias::LOCATION,
                'destination' => $destinationId,
                'isHistory' => false,
                'isCustom' => true,
                'forward' => true,
            ]
        );
        $request = $this->getRequestByPathInfo($pathInfo);
        $this->urlAliasService
            ->expects(self::once())
            ->method('lookup')
            ->with($pathInfo)
            ->will(self::returnValue($urlAlias));
        $this->urlAliasGenerator
            ->expects(self::once())
            ->method('loadLocation')
            ->with($destinationId)
            ->will(
                self::returnValue($destinationLocation)
            );
        $this->urlAliasGenerator
            ->expects(self::once())
            ->method('generate')
            ->with($destinationLocation)
            ->will(self::returnValue($newPathInfo));
        $this->urlAliasGenerator
            ->expects(self::once())
            ->method('loadLocation')
            ->will(self::returnValue($destinationLocation));

        $expected = [
            '_route' => UrlAliasRouter::URL_ALIAS_ROUTE_NAME,
            '_controller' => UrlAliasRouter::VIEW_ACTION,
            'locationId' => $destinationId,
            'contentId' => 456,
            'viewType' => ViewManager::VIEW_TYPE_FULL,
            'layout' => true,
            'needsRedirect' => true,
            'semanticPathinfo' => $newPathInfo,
            'prependSiteaccessOnRedirect' => false,
        ];
        self::assertEquals($expected, $this->router->matchRequest($request));
    }

    public function testMatchRequestFail(): void
    {
        $this->expectException(ResourceNotFoundException::class);

        $pathInfo = '/foo/bar';
        $request = $this->getRequestByPathInfo($pathInfo);
        $this->urlAliasService
            ->expects(self::once())
            ->method('lookup')
            ->with($pathInfo)
            ->will(self::throwException(new NotFoundException('URLAlias', $pathInfo)));
        $this->router->matchRequest($request);
    }

    public function testMatchRequestResource(): void
    {
        $pathInfo = '/hello_content/hello_search';
        $destination = '/content/search';
        $urlAlias = new URLAlias(
            [
                'destination' => $destination,
                'path' => $pathInfo,
                'type' => URLAlias::RESOURCE,
            ]
        );
        $request = $this->getRequestByPathInfo($pathInfo);
        $this->urlAliasService
            ->expects(self::once())
            ->method('lookup')
            ->with($pathInfo)
            ->will(self::returnValue($urlAlias));

        $expected = [
            '_route' => UrlAliasRouter::URL_ALIAS_ROUTE_NAME,
            'semanticPathinfo' => $destination,
            'needsForward' => true,
        ];
        self::assertEquals($expected, $this->router->matchRequest($request));
    }

    public function testMatchRequestResourceWithRedirect(): void
    {
        $pathInfo = '/hello_content/hello_search';
        $destination = '/content/search';
        $urlAlias = new URLAlias(
            [
                'destination' => $destination,
                'path' => $pathInfo,
                'type' => URLAlias::RESOURCE,
                'forward' => true,
            ]
        );
        $request = $this->getRequestByPathInfo($pathInfo);
        $this->urlAliasService
            ->expects(self::once())
            ->method('lookup')
            ->with($pathInfo)
            ->will(self::returnValue($urlAlias));

        $expected = [
            '_route' => UrlAliasRouter::URL_ALIAS_ROUTE_NAME,
            'needsRedirect' => true,
            'semanticPathinfo' => $destination,
        ];
        self::assertEquals($expected, $this->router->matchRequest($request));
    }

    public function testMatchRequestResourceWithCaseRedirect(): void
    {
        $pathInfo = '/heLLo_contEnt/hEllo_SEarch';
        $urlAliasPath = '/hello_content/hello_search';
        $destination = '/content/search';
        $urlAlias = new URLAlias(
            [
                'destination' => $destination,
                'path' => $urlAliasPath,
                'type' => URLAlias::RESOURCE,
                'forward' => false,
            ]
        );
        $request = $this->getRequestByPathInfo($pathInfo);
        $this->urlAliasGenerator
            ->expects(self::once())
            ->method('isUriPrefixExcluded')
            ->with($pathInfo)
            ->will(self::returnValue(false));
        $this->urlAliasService
            ->expects(self::once())
            ->method('lookup')
            ->with($pathInfo)
            ->will(self::returnValue($urlAlias));

        $expected = [
            '_route' => UrlAliasRouter::URL_ALIAS_ROUTE_NAME,
            'semanticPathinfo' => $urlAliasPath,
            'needsRedirect' => true,
        ];
        self::assertEquals($expected, $this->router->matchRequest($request));
    }

    /**
     * Tests that forwarding custom alias will redirect to the resource destination rather than
     * to the case-corrected alias.
     */
    public function testMatchRequestResourceCaseIncorrectWithForwardRedirect(): void
    {
        $pathInfo = '/heLLo_contEnt/hEllo_SEarch';
        $urlAliasPath = '/hello_content/hello_search';
        $destination = '/content/search';
        $urlAlias = new URLAlias(
            [
                'destination' => $destination,
                'path' => $urlAliasPath,
                'type' => URLAlias::RESOURCE,
                'forward' => true,
            ]
        );
        $request = $this->getRequestByPathInfo($pathInfo);
        $this->urlAliasService
            ->expects(self::once())
            ->method('lookup')
            ->with($pathInfo)
            ->will(self::returnValue($urlAlias));

        $expected = [
            '_route' => UrlAliasRouter::URL_ALIAS_ROUTE_NAME,
            'semanticPathinfo' => $destination,
            'needsRedirect' => true,
        ];
        self::assertEquals($expected, $this->router->matchRequest($request));
    }

    public function testMatchRequestVirtual(): void
    {
        $pathInfo = '/foo/bar';
        $urlAlias = new URLAlias(
            [
                'path' => $pathInfo,
                'type' => URLAlias::VIRTUAL,
            ]
        );
        $request = $this->getRequestByPathInfo($pathInfo);
        $this->urlAliasService
            ->expects(self::once())
            ->method('lookup')
            ->with($pathInfo)
            ->will(self::returnValue($urlAlias));

        $expected = [
            '_route' => UrlAliasRouter::URL_ALIAS_ROUTE_NAME,
            'semanticPathinfo' => '/',
            'needsForward' => true,
        ];
        self::assertEquals($expected, $this->router->matchRequest($request));
    }

    public function testMatchRequestVirtualWithCaseRedirect(): void
    {
        $pathInfo = '/Foo/bAR';
        $urlAliasPath = '/foo/bar';
        $urlAlias = new URLAlias(
            [
                'path' => $urlAliasPath,
                'type' => URLAlias::VIRTUAL,
            ]
        );
        $request = $this->getRequestByPathInfo($pathInfo);
        $this->urlAliasGenerator
            ->expects(self::once())
            ->method('isUriPrefixExcluded')
            ->with($pathInfo)
            ->will(self::returnValue(false));
        $this->urlAliasService
            ->expects(self::once())
            ->method('lookup')
            ->with($pathInfo)
            ->will(self::returnValue($urlAlias));

        $expected = [
            '_route' => UrlAliasRouter::URL_ALIAS_ROUTE_NAME,
            'semanticPathinfo' => $urlAliasPath,
            'needsRedirect' => true,
        ];
        self::assertEquals($expected, $this->router->matchRequest($request));
    }

    public function testGenerateFail(): void
    {
        $this->expectException(RouteNotFoundException::class);

        $this->router->generate('invalidRoute');
    }

    public function testGenerateWithRouteObject(): void
    {
        $location = new Location(['id' => 54]);
        $parameters = [
            'some' => 'thing',
        ];

        $referenceType = UrlGeneratorInterface::ABSOLUTE_PATH;
        $generatedLink = '/foo/bar';

        $this->urlAliasGenerator
            ->expects(self::once())
            ->method('generate')
            ->with($location, $parameters, $referenceType)
            ->will(self::returnValue($generatedLink));

        self::assertSame(
            $generatedLink,
            $this->router->generate(
                '',
                $parameters + [
                    RouteObjectInterface::ROUTE_OBJECT => $location,
                ],
                $referenceType
            )
        );
    }

    public function testGenerateNoLocation(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->router->generate(UrlAliasRouter::URL_ALIAS_ROUTE_NAME, ['foo' => 'bar']);
    }

    public function testGenerateInvalidLocation(): void
    {
        $this->expectException(LogicException::class);

        $this->router->generate(UrlAliasRouter::URL_ALIAS_ROUTE_NAME, ['location' => new stdClass()]);
    }

    public function testGenerateWithLocationId(): void
    {
        $locationId = 123;
        $location = new Location(['id' => $locationId]);
        $parameters = ['some' => 'thing'];
        $referenceType = UrlGeneratorInterface::ABSOLUTE_PATH;
        $generatedLink = '/foo/bar';
        $this->locationService
            ->expects(self::once())
            ->method('loadLocation')
            ->with($locationId)
            ->will(self::returnValue($location));
        $this->urlAliasGenerator
            ->expects(self::once())
            ->method('generate')
            ->with($location, $parameters, $referenceType)
            ->will(self::returnValue($generatedLink));
        self::assertSame(
            $generatedLink,
            $this->router->generate(
                UrlAliasRouter::URL_ALIAS_ROUTE_NAME,
                $parameters + ['locationId' => $locationId],
                $referenceType
            )
        );
    }

    public function testGenerateWithLocationAsParameter(): void
    {
        $locationId = 123;
        $location = new Location(['id' => $locationId]);
        $parameters = ['some' => 'thing'];
        $referenceType = UrlGeneratorInterface::ABSOLUTE_PATH;
        $generatedLink = '/foo/bar';
        $this->urlAliasGenerator
            ->expects(self::once())
            ->method('generate')
            ->with($location, $parameters, $referenceType)
            ->will(self::returnValue($generatedLink));
        self::assertSame(
            $generatedLink,
            $this->router->generate(
                UrlAliasRouter::URL_ALIAS_ROUTE_NAME,
                $parameters + ['location' => $location],
                $referenceType
            )
        );
    }

    public function testGenerateWithContentId(): void
    {
        $locationId = 123;
        $contentId = 456;
        $location = new Location(['id' => $locationId]);
        $contentInfo = new ContentInfo(['id' => $contentId, 'mainLocationId' => $locationId]);
        $parameters = ['some' => 'thing'];
        $referenceType = UrlGeneratorInterface::ABSOLUTE_PATH;
        $generatedLink = '/foo/bar';
        $this->contentService
            ->expects(self::once())
            ->method('loadContentInfo')
            ->with($contentId)
            ->will(self::returnValue($contentInfo));
        $this->locationService
            ->expects(self::once())
            ->method('loadLocation')
            ->with($contentInfo->mainLocationId)
            ->will(self::returnValue($location));
        $this->urlAliasGenerator
            ->expects(self::once())
            ->method('generate')
            ->with($location, $parameters, $referenceType)
            ->will(self::returnValue($generatedLink));
        self::assertSame(
            $generatedLink,
            $this->router->generate(
                UrlAliasRouter::URL_ALIAS_ROUTE_NAME,
                $parameters + ['contentId' => $contentId],
                $referenceType
            )
        );
    }

    public function testGenerateWithContentIdWithMissingMainLocation(): void
    {
        $this->expectException(LogicException::class);

        $contentId = 456;
        $contentInfo = new ContentInfo(['id' => $contentId, 'mainLocationId' => null]);
        $parameters = ['some' => 'thing'];
        $referenceType = UrlGeneratorInterface::ABSOLUTE_PATH;
        $this->contentService
            ->expects(self::once())
            ->method('loadContentInfo')
            ->with($contentId)
            ->will(self::returnValue($contentInfo));

        $this->router->generate(
            UrlAliasRouter::URL_ALIAS_ROUTE_NAME,
            $parameters + ['contentId' => $contentId],
            $referenceType
        );
    }
}
