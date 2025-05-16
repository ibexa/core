<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Core\MVC\Symfony\Templating;

use Ibexa\Contracts\Core\Repository\LocationService;
use Ibexa\Contracts\Core\Repository\Values\Content\Location;
use Ibexa\Contracts\Core\SiteAccess\ConfigResolverInterface;
use Ibexa\Core\Helper\TranslationHelper;
use Ibexa\Core\MVC\Symfony\Routing\UrlAliasRouter;
use Ibexa\Core\MVC\Symfony\SiteAccess;
use Ibexa\Core\MVC\Symfony\Templating\GlobalHelper;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\RouterInterface;

class GlobalHelperTest extends TestCase
{
    /** @var \Ibexa\Core\MVC\Symfony\Templating\GlobalHelper */
    protected $helper;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $container;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $locationService;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $configResolver;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $router;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $translationHelper;

    protected function setUp(): void
    {
        parent::setUp();

        $this->container = $this->createMock(ContainerInterface::class);
        $this->locationService = $this->createMock(LocationService::class);
        $this->configResolver = $this->createMock(ConfigResolverInterface::class);
        $this->router = $this->createMock(RouterInterface::class);
        $this->translationHelper = $this->createMock(TranslationHelper::class);
        $this->helper = new GlobalHelper($this->configResolver, $this->locationService, $this->router, $this->translationHelper);
    }

    public function testGetSiteaccess()
    {
        $request = new Request();
        $requestStack = new RequestStack([$request]);
        $siteAccess = $this->createMock(SiteAccess::class);
        $request->attributes->set('siteaccess', $siteAccess);
        $this->helper->setRequestStack($requestStack);

        self::assertSame($siteAccess, $this->helper->getSiteaccess());
    }

    public function testGetViewParameters()
    {
        $request = Request::create('/foo');
        $viewParameters = [
            'foo' => 'bar',
            'toto' => 'tata',
            'somethingelse' => 'héhé-høhø',
        ];
        $request->attributes->set('viewParameters', $viewParameters);
        $requestStack = new RequestStack([$request]);
        $this->helper->setRequestStack($requestStack);

        self::assertSame($viewParameters, $this->helper->getViewParameters());
    }

    public function testGetViewParametersString()
    {
        $request = Request::create('/foo');
        $viewParametersString = '/(foo)/bar/(toto)/tata/(somethingelse)/héhé-høhø';
        $request->attributes->set('viewParametersString', $viewParametersString);
        $requestStack = new RequestStack([$request]);
        $this->helper->setRequestStack($requestStack);

        self::assertSame($viewParametersString, $this->helper->getViewParametersString());
    }

    public function testGetRequestedUriString()
    {
        $request = Request::create('/ibexa_demo_site/foo/bar');
        $semanticPathinfo = '/foo/bar';
        $request->attributes->set('semanticPathinfo', $semanticPathinfo);
        $requestStack = new RequestStack([$request]);
        $this->helper->setRequestStack($requestStack);

        self::assertSame($semanticPathinfo, $this->helper->getRequestedUriString());
    }

    public function testGetSystemUriStringNoUrlAlias()
    {
        $request = Request::create('/ibexa_demo_site/foo/bar');
        $semanticPathinfo = '/foo/bar';
        $request->attributes->set('semanticPathinfo', $semanticPathinfo);
        $request->attributes->set('_route', 'someRouteName');
        $requestStack = new RequestStack([$request]);
        $this->helper->setRequestStack($requestStack);
        self::assertSame($semanticPathinfo, $this->helper->getSystemUriString());
    }

    public function testGetSystemUriString()
    {
        $locationId = 123;
        $contentId = 456;
        $viewType = 'full';
        $expectedSystemUriString = '/view/content/456/full/1/123';
        $request = Request::create('/ibexa_demo_site/foo/bar');
        $request->attributes->set('_route', UrlAliasRouter::URL_ALIAS_ROUTE_NAME);
        $request->attributes->set('contentId', $contentId);
        $request->attributes->set('locationId', $locationId);
        $request->attributes->set('viewType', $viewType);
        $requestStack = new RequestStack([$request]);

        $this->router
            ->expects(self::once())
            ->method('generate')
            ->with('ibexa.content.view', [
                'contentId' => $contentId,
                'locationId' => $locationId,
                'viewType' => $viewType,
            ])
            ->will(self::returnValue($expectedSystemUriString));

        $this->helper->setRequestStack($requestStack);

        self::assertSame($expectedSystemUriString, $this->helper->getSystemUriString());
    }

    public function testGetConfigResolver()
    {
        self::assertSame($this->configResolver, $this->helper->getConfigResolver());
    }

    public function testGetRootLocation()
    {
        $rootLocationId = 2;
        $this->configResolver
            ->expects(self::once())
            ->method('getParameter')
            ->with('content.tree_root.location_id')
            ->will(self::returnValue($rootLocationId));

        $rootLocation = $this
            ->getMockBuilder(Location::class)
            ->setConstructorArgs([['id' => $rootLocationId]])
            ->getMock();

        $this->locationService
            ->expects(self::once())
            ->method('loadLocation')
            ->with($rootLocationId)
            ->will(self::returnValue($rootLocation));

        self::assertSame($rootLocation, $this->helper->getRootLocation());
    }

    public function testGetTranslationSiteAccess()
    {
        $language = 'fre-FR';
        $siteaccess = 'fre';
        $this->translationHelper
            ->expects(self::once())
            ->method('getTranslationSiteAccess')
            ->with($language)
            ->will(self::returnValue($siteaccess));

        self::assertSame($siteaccess, $this->helper->getTranslationSiteAccess($language));
    }

    public function testGetAvailableLanguages()
    {
        $languages = ['fre-FR', 'eng-GB', 'esl-ES'];
        $this->translationHelper
            ->expects(self::once())
            ->method('getAvailableLanguages')
            ->will(self::returnValue($languages));

        self::assertSame($languages, $this->helper->getAvailableLanguages());
    }
}
