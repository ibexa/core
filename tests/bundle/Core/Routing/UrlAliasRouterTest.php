<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Bundle\Core\Routing;

use Ibexa\Bundle\Core\Routing\UrlAliasRouter;
use Ibexa\Contracts\Core\Repository\ContentService;
use Ibexa\Contracts\Core\Repository\LocationService;
use Ibexa\Contracts\Core\Repository\URLAliasService;
use Ibexa\Contracts\Core\Repository\Values\Content\ContentInfo;
use Ibexa\Contracts\Core\Repository\Values\Content\URLAlias;
use Ibexa\Contracts\Core\SiteAccess\ConfigResolverInterface;
use Ibexa\Core\MVC\Symfony\Routing\Generator\UrlAliasGenerator;
use Ibexa\Core\MVC\Symfony\View\Manager as ViewManager;
use Ibexa\Core\Repository\Values\Content\Location;
use Ibexa\Tests\Core\MVC\Symfony\Routing\UrlAliasRouterTest as BaseUrlAliasRouterTest;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\RequestContext;

class UrlAliasRouterTest extends BaseUrlAliasRouterTest
{
    /** @var MockObject */
    private $configResolver;

    protected function setUp(): void
    {
        $this->configResolver = $this->createMock(ConfigResolverInterface::class);
        $this->configResolver
            ->expects(self::any())
            ->method('getParameter')
            ->will(
                self::returnValueMap(
                    [
                        ['url_alias_router', null, null, true],
                        ['content.tree_root.location_id', null, null, null],
                        ['content.tree_root.excluded_uri_prefixes', null, null, []],
                    ]
                )
            );
        parent::setUp();
    }

    protected function getRouter(
        LocationService $locationService,
        URLAliasService $urlAliasService,
        ContentService $contentService,
        UrlAliasGenerator $urlAliasGenerator,
        RequestContext $requestContext
    ) {
        $router = new UrlAliasRouter($locationService, $urlAliasService, $contentService, $urlAliasGenerator, $requestContext);
        $router->setConfigResolver($this->configResolver);

        return $router;
    }

    /**
     * Resets container and configResolver mocks.
     */
    protected function resetConfigResolver()
    {
        $this->configResolver = $this->createMock(ConfigResolverInterface::class);
        $this->router->setConfigResolver($this->configResolver);
    }

    public function testMatchRequestDeactivatedUrlAlias()
    {
        $this->expectException(ResourceNotFoundException::class);

        $this->resetConfigResolver();
        $this->configResolver
            ->expects(self::any())
            ->method('getParameter')
            ->will(
                self::returnValueMap(
                    [
                        ['url_alias_router', null, null, false],
                    ]
                )
            );
        $this->router->matchRequest($this->getRequestByPathInfo('/foo'));
    }

    public function testMatchRequestWithRootLocation()
    {
        $rootLocationId = 123;
        $this->resetConfigResolver();
        $this->configResolver
            ->expects(self::any())
            ->method('getParameter')
            ->will(
                self::returnValueMap(
                    [
                        ['url_alias_router', null, null, true],
                    ]
                )
            );
        $this->router->setRootLocationId($rootLocationId);

        $prefix = '/root/prefix';
        $this->urlALiasGenerator
            ->expects(self::exactly(2))
            ->method('getPathPrefixByRootLocationId')
            ->with($rootLocationId)
            ->will(self::returnValue($prefix));

        $locationId = 789;
        $path = '/foo/bar';
        $urlAlias = new URLAlias(
            [
                'destination' => $locationId,
                'path' => $prefix . $path,
                'type' => URLAlias::LOCATION,
                'isHistory' => false,
            ]
        );
        $this->urlAliasService
            ->expects(self::once())
            ->method('lookup')
            ->with($prefix . $path)
            ->will(self::returnValue($urlAlias));

        $this->urlALiasGenerator
            ->expects(self::once())
            ->method('loadLocation')
            ->will(self::returnValue(new Location(['contentInfo' => new ContentInfo(['id' => 456])])));

        $expected = [
            '_route' => UrlAliasRouter::URL_ALIAS_ROUTE_NAME,
            '_controller' => UrlAliasRouter::VIEW_ACTION,
            'locationId' => $locationId,
            'contentId' => 456,
            'viewType' => ViewManager::VIEW_TYPE_FULL,
            'layout' => true,
        ];
        $request = $this->getRequestByPathInfo($path);
        self::assertEquals($expected, $this->router->matchRequest($request));
    }

    public function testMatchRequestLocationCaseRedirectWithRootLocation()
    {
        $rootLocationId = 123;
        $this->resetConfigResolver();
        $this->configResolver
            ->expects(self::any())
            ->method('getParameter')
            ->will(
                self::returnValueMap(
                    [
                        ['url_alias_router', null, null, true],
                    ]
                )
            );
        $this->router->setRootLocationId($rootLocationId);

        $prefix = '/root/prefix';
        $this->urlALiasGenerator
            ->expects(self::exactly(2))
            ->method('getPathPrefixByRootLocationId')
            ->with($rootLocationId)
            ->will(self::returnValue($prefix));
        $this->urlALiasGenerator
            ->expects(self::once())
            ->method('loadLocation')
            ->will(self::returnValue(new Location(['contentInfo' => new ContentInfo(['id' => 456])])));

        $locationId = 789;
        $path = '/foo/bar';
        $requestedPath = '/Foo/Bar';
        $urlAlias = new URLAlias(
            [
                'destination' => $locationId,
                'path' => $prefix . $path,
                'type' => URLAlias::LOCATION,
                'isHistory' => false,
            ]
        );
        $this->urlAliasService
            ->expects(self::once())
            ->method('lookup')
            ->with($prefix . $requestedPath)
            ->will(self::returnValue($urlAlias));

        $expected = [
            '_route' => UrlAliasRouter::URL_ALIAS_ROUTE_NAME,
            '_controller' => UrlAliasRouter::VIEW_ACTION,
            'locationId' => $locationId,
            'contentId' => 456,
            'viewType' => ViewManager::VIEW_TYPE_FULL,
            'layout' => true,
            'semanticPathinfo' => $path,
            'needsRedirect' => true,
        ];
        $request = $this->getRequestByPathInfo($requestedPath);
        self::assertEquals($expected, $this->router->matchRequest($request));
    }

    public function testMatchRequestLocationCaseRedirectWithRootRootLocation()
    {
        $rootLocationId = 123;
        $this->resetConfigResolver();
        $this->configResolver
            ->expects(self::any())
            ->method('getParameter')
            ->will(
                self::returnValueMap(
                    [
                        ['url_alias_router', null, null, true],
                    ]
                )
            );
        $this->router->setRootLocationId($rootLocationId);

        $prefix = '/';
        $this->urlALiasGenerator
            ->expects(self::exactly(2))
            ->method('getPathPrefixByRootLocationId')
            ->with($rootLocationId)
            ->will(self::returnValue($prefix));

        $locationId = 789;
        $path = '/foo/bar';
        $requestedPath = '/Foo/Bar';
        $urlAlias = new URLAlias(
            [
                'destination' => $locationId,
                'path' => $path,
                'type' => URLAlias::LOCATION,
                'isHistory' => false,
            ]
        );
        $this->urlAliasService
            ->expects(self::once())
            ->method('lookup')
            ->with($requestedPath)
            ->will(self::returnValue($urlAlias));
        $this->urlALiasGenerator
            ->expects(self::once())
            ->method('loadLocation')
            ->will(self::returnValue(new Location(['contentInfo' => new ContentInfo(['id' => 456])])));

        $expected = [
            '_route' => UrlAliasRouter::URL_ALIAS_ROUTE_NAME,
            '_controller' => UrlAliasRouter::VIEW_ACTION,
            'locationId' => $locationId,
            'contentId' => 456,
            'viewType' => ViewManager::VIEW_TYPE_FULL,
            'layout' => true,
            'semanticPathinfo' => $path,
            'needsRedirect' => true,
        ];
        $request = $this->getRequestByPathInfo($requestedPath);
        self::assertEquals($expected, $this->router->matchRequest($request));
    }

    public function testMatchRequestResourceCaseRedirectWithRootLocation()
    {
        $rootLocationId = 123;
        $this->resetConfigResolver();
        $this->configResolver
            ->expects(self::any())
            ->method('getParameter')
            ->will(
                self::returnValueMap(
                    [
                        ['url_alias_router', null, null, true],
                    ]
                )
            );
        $this->router->setRootLocationId($rootLocationId);

        $prefix = '/root/prefix';
        $this->urlALiasGenerator
            ->expects(self::exactly(2))
            ->method('getPathPrefixByRootLocationId')
            ->with($rootLocationId)
            ->will(self::returnValue($prefix));

        $path = '/foo/bar';
        $requestedPath = '/Foo/Bar';
        $urlAlias = new URLAlias(
            [
                'destination' => '/content/search',
                'path' => $prefix . $path,
                'type' => URLAlias::RESOURCE,
                'isHistory' => false,
            ]
        );
        $this->urlAliasService
            ->expects(self::once())
            ->method('lookup')
            ->with($prefix . $requestedPath)
            ->will(self::returnValue($urlAlias));

        $expected = [
            '_route' => UrlAliasRouter::URL_ALIAS_ROUTE_NAME,
            'semanticPathinfo' => $path,
            'needsRedirect' => true,
        ];
        $request = $this->getRequestByPathInfo($requestedPath);
        self::assertEquals($expected, $this->router->matchRequest($request));
    }

    public function testMatchRequestVirtualCaseRedirectWithRootLocation()
    {
        $rootLocationId = 123;
        $this->resetConfigResolver();
        $this->configResolver
            ->expects(self::any())
            ->method('getParameter')
            ->will(
                self::returnValueMap(
                    [
                        ['url_alias_router', null, null, true],
                    ]
                )
            );
        $this->router->setRootLocationId($rootLocationId);

        $prefix = '/root/prefix';
        $this->urlALiasGenerator
            ->expects(self::exactly(2))
            ->method('getPathPrefixByRootLocationId')
            ->with($rootLocationId)
            ->will(self::returnValue($prefix));

        $path = '/foo/bar';
        $requestedPath = '/Foo/Bar';
        $urlAlias = new URLAlias(
            [
                'path' => $prefix . $path,
                'type' => URLAlias::VIRTUAL,
            ]
        );
        $this->urlAliasService
            ->expects(self::once())
            ->method('lookup')
            ->with($prefix . $requestedPath)
            ->will(self::returnValue($urlAlias));

        $expected = [
            '_route' => UrlAliasRouter::URL_ALIAS_ROUTE_NAME,
            'semanticPathinfo' => $path,
            'needsRedirect' => true,
        ];
        $request = $this->getRequestByPathInfo($requestedPath);
        self::assertEquals($expected, $this->router->matchRequest($request));
    }

    public function testMatchRequestWithRootLocationAndExclusion()
    {
        $this->resetConfigResolver();
        $this->configResolver
            ->expects(self::any())
            ->method('getParameter')
            ->will(
                self::returnValueMap(
                    [
                        ['url_alias_router', null, null, true],
                        ['content.tree_root.location_id', null, null, 123],
                        ['content.tree_root.excluded_uri_prefixes', null, null, ['/shared/content']],
                    ]
                )
            );
        $this->router->setRootLocationId(123);

        $pathInfo = '/shared/content/foo-bar';
        $destinationId = 789;
        $this->urlALiasGenerator
            ->expects(self::any())
            ->method('isUriPrefixExcluded')
            ->with($pathInfo)
            ->will(self::returnValue(true));

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
        $this->urlALiasGenerator
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
}
