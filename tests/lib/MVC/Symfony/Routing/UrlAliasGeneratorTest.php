<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Core\MVC\Symfony\Routing;

use Ibexa\Contracts\Core\Persistence\User\Handler as SPIUserHandler;
use Ibexa\Contracts\Core\Repository\LocationService;
use Ibexa\Contracts\Core\Repository\URLAliasService;
use Ibexa\Contracts\Core\Repository\Values\Content\ContentInfo;
use Ibexa\Contracts\Core\Repository\Values\Content\URLAlias;
use Ibexa\Contracts\Core\SiteAccess\ConfigResolverInterface;
use Ibexa\Core\MVC\Symfony\Routing\Generator\UrlAliasGenerator;
use Ibexa\Core\MVC\Symfony\SiteAccess;
use Ibexa\Core\MVC\Symfony\SiteAccess\SiteAccessRouterInterface;
use Ibexa\Core\Repository\Mapper\RoleDomainMapper;
use Ibexa\Core\Repository\Permission\LimitationService;
use Ibexa\Core\Repository\Permission\PermissionResolver;
use Ibexa\Core\Repository\Repository;
use Ibexa\Core\Repository\Values\Content\Location;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Routing\RouterInterface;

class UrlAliasGeneratorTest extends TestCase
{
    private URLAliasService & MockObject $urlAliasService;

    private LocationService & MockObject $locationService;

    private RouterInterface & MockObject $router;

    private LoggerInterface & MockObject $logger;

    private SiteAccessRouterInterface & MockObject $siteAccessRouter;

    private ConfigResolverInterface & MockObject $configResolver;

    /** @var \Ibexa\Core\MVC\Symfony\Routing\Generator\UrlAliasGenerator */
    private UrlAliasGenerator $urlAliasGenerator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->router = $this->createMock(RouterInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->siteAccessRouter = $this->createMock(SiteAccessRouterInterface::class);
        $this->configResolver = $this->createMock(ConfigResolverInterface::class);
        $repositoryClass = Repository::class;
        /** @var \Ibexa\Core\Repository\Repository&\PHPUnit\Framework\MockObject\MockObject $repository */
        $repository = $this
            ->getMockBuilder($repositoryClass)
            ->disableOriginalConstructor()
            ->onlyMethods(
                array_diff(
                    get_class_methods($repositoryClass),
                    ['sudo']
                )
            )
            ->getMock();
        $this->urlAliasService = $this->createMock(URLAliasService::class);
        $this->locationService = $this->createMock(LocationService::class);
        $repository
            ->method('getURLAliasService')
            ->willReturn($this->urlAliasService);
        $repository
            ->method('getLocationService')
            ->willReturn($this->locationService);
        $repository
            ->method('getPermissionResolver')
            ->willReturn($this->getPermissionResolverMock());

        $urlAliasCharmap = [
            '"' => '%22',
            "'" => '%27',
            '<' => '%3C',
            '>' => '%3E',
        ];
        $this->urlAliasGenerator = new UrlAliasGenerator(
            $repository,
            $this->router,
            $this->configResolver,
            $urlAliasCharmap
        );
        $this->urlAliasGenerator->setLogger($this->logger);
        $this->urlAliasGenerator->setSiteAccessRouter($this->siteAccessRouter);
    }

    public function testGetPathPrefixByRootLocationId(): void
    {
        $rootLocationId = 123;
        $rootLocation = new Location(['id' => $rootLocationId]);
        $pathPrefix = '/foo/bar';
        $rootUrlAlias = new URLAlias(['path' => $pathPrefix]);
        $this->locationService
            ->expects(self::once())
            ->method('loadLocation')
            ->with($rootLocationId)
            ->will(self::returnValue($rootLocation));
        $this->urlAliasService
            ->expects(self::once())
            ->method('reverseLookup')
            ->with($rootLocation)
            ->will(self::returnValue($rootUrlAlias));

        self::assertSame($pathPrefix, $this->urlAliasGenerator->getPathPrefixByRootLocationId($rootLocationId));
    }

    /**
     * @dataProvider providerTestIsPrefixExcluded
     */
    public function testIsPrefixExcluded(string $uri, bool $expectedIsExcluded): void
    {
        $this->urlAliasGenerator->setExcludedUriPrefixes(
            [
                '/products',
                '/shared/content',
                '/something/in-the-way/',
            ]
        );
        self::assertSame($expectedIsExcluded, $this->urlAliasGenerator->isUriPrefixExcluded($uri));
    }

    public function providerTestIsPrefixExcluded(): array
    {
        return [
            ['/foo/bar', false],
            ['/products/bar', true],
            ['/ProDUctS/Ibexa', true],
            ['/ProductsFoo/Ibexa', true],
            ['/shared/foo', false],
            ['/SHARED/contenT/bar', true],
            ['/SomeThing/bidule/chose', false],
            ['/SomeThing/in-the-way/truc/', true],
            ['/SomeThing/in-the-way-suffixed/', false],
            ['/CMS/Ibexa', false],
            ['/Lyon/Best/city', false],
        ];
    }

    public function testLoadLocation(): void
    {
        $locationId = 123;
        $location = new Location(['id' => $locationId]);
        $this->locationService
            ->expects(self::once())
            ->method('loadLocation')
            ->with($locationId)
            ->will(self::returnValue($location));
        $this->urlAliasGenerator->loadLocation($locationId);
    }

    /**
     * @dataProvider providerTestDoGenerate
     */
    public function testDoGenerate(URLAlias $urlAlias, array $parameters, string $expected): void
    {
        $location = new Location(['id' => 123]);
        $this->urlAliasService
            ->expects(self::once())
            ->method('listLocationAliases')
            ->with($location, false)
            ->will(self::returnValue([$urlAlias]));

        $this->urlAliasGenerator->setSiteAccess(new SiteAccess('test', 'fake', $this->createMock(SiteAccess\URILexer::class)));

        self::assertSame($expected, $this->urlAliasGenerator->doGenerate($location, $parameters));
    }

    public function providerTestDoGenerate(): array
    {
        return [
            'without_parameters' => [
                new URLAlias(['path' => '/foo/bar']),
                [],
                '/foo/bar',
            ],
            'one_parameter' => [
                new URLAlias(['path' => '/foo/bar']),
                ['some' => 'thing'],
                '/foo/bar?some=thing',
            ],
            'two_parameters' => [
                new URLAlias(['path' => '/foo/bar']),
                ['some' => 'thing', 'truc' => 'muche'],
                '/foo/bar?some=thing&truc=muche',
            ],
            '_fragment in parameters' => [
                new URLAlias(['path' => '/foo/bar']),
                ['some' => 'thing', 'truc' => 'muche', '_fragment' => 'foo'],
                '/foo/bar?some=thing&truc=muche#foo',
            ],
        ];
    }

    /**
     * @dataProvider providerTestDoGenerateWithSiteaccess
     *
     * @param array $parameters
     */
    public function testDoGenerateWithSiteAccessParam(URLAlias $urlAlias, array $parameters, string $expected): void
    {
        $siteaccessName = 'foo';
        $parameters += ['siteaccess' => $siteaccessName];
        $languages = ['esl-ES', 'fre-FR', 'eng-GB'];

        $saRootLocations = [
            'foo' => 2,
            'bar' => 100,
        ];
        $treeRootUrlAlias = [
            2 => new URLAlias(['path' => '/']),
            100 => new URLAlias(['path' => '/foo/bar']),
        ];

        $this->configResolver
            ->expects(self::any())
            ->method('getParameter')
            ->will(
                self::returnValueMap(
                    [
                        ['languages', null, 'foo', $languages],
                        ['languages', null, 'bar', $languages],
                        ['content.tree_root.location_id', null, 'foo', $saRootLocations['foo']],
                        ['content.tree_root.location_id', null, 'bar', $saRootLocations['bar']],
                    ]
                )
            );

        $location = new Location(['id' => 123]);
        $this->urlAliasService
            ->expects(self::exactly(1))
            ->method('listLocationAliases')
            ->will(
                self::returnValueMap(
                    [
                        [$location, false, null, null, $languages, [$urlAlias]],
                    ]
                )
            );

        $this->locationService
            ->expects(self::once())
            ->method('loadLocation')
            ->will(
                self::returnCallback(
                    static function ($locationId): Location {
                        return new Location(['id' => $locationId]);
                    }
                )
            );
        $this->urlAliasService
            ->expects(self::exactly(1))
            ->method('reverseLookup')
            ->will(
                self::returnCallback(
                    static function ($location) use ($treeRootUrlAlias): \Ibexa\Contracts\Core\Repository\Values\Content\URLAlias {
                        return $treeRootUrlAlias[$location->id];
                    }
                )
            );

        $this->urlAliasGenerator->setSiteAccess(new SiteAccess('test', 'fake', $this->createMock(SiteAccess\URILexer::class)));

        self::assertSame($expected, $this->urlAliasGenerator->doGenerate($location, $parameters));
    }

    public function providerTestDoGenerateWithSiteaccess(): array
    {
        return [
            [
                new URLAlias(['path' => '/foo/bar']),
                [],
                '/foo/bar',
            ],
            [
                new URLAlias(['path' => '/foo/bar/baz']),
                ['siteaccess' => 'bar'],
                '/baz',
            ],
            [
                new URLAlias(['path' => '/special-chars-"<>\'']),
                [],
                '/special-chars-%22%3C%3E%27',
            ],
            'fragment' => [
                new URLAlias(['path' => '/foo/bar']),
                ['_fragment' => 'qux'],
                '/foo/bar#qux',
            ],
            'fragment_and_siteaccess' => [
                new URLAlias(['path' => '/foo/bar/baz']),
                ['_fragment' => 'qux', 'siteaccess' => 'bar'],
                '/baz#qux',
            ],
            'fragment_and_special_chars' => [
                new URLAlias(['path' => '/special-chars-"<>\'']),
                ['_fragment' => 'qux'],
                '/special-chars-%22%3C%3E%27#qux',
            ],
            'fragment_site_siteaccess_and_params' => [
                new URLAlias(['path' => '/foo/bar/baz']),
                ['_fragment' => 'qux', 'siteaccess' => 'bar', 'some' => 'foo'],
                '/baz?some=foo#qux',
            ],
        ];
    }

    public function testDoGenerateWithSiteAccessLoadsLocationWithLanguages(): void
    {
        $siteSiteAccess = 'site';
        $gerSiteAccess = 'ger';
        $parameters = ['siteaccess' => $gerSiteAccess];

        $saRootLocations = [
            $siteSiteAccess => $siteSiteAccessLocationId = 2,
            $gerSiteAccess => $gerSiteAccessLocationId = 71,
        ];
        $treeRootUrlAliases = [
            $siteSiteAccessLocationId => new URLAlias(['path' => '/']),
            $gerSiteAccessLocationId => new URLAlias(['path' => '/ger']),
        ];

        $this->configResolver
            ->expects(self::any())
            ->method('getParameter')
            ->will(
                self::returnValueMap(
                    [
                        ['languages', null, $siteSiteAccess, ['eng-GB']],
                        ['languages', null, $gerSiteAccess, ['ger-DE']],
                        [
                            'content.tree_root.location_id',
                            null,
                            $siteSiteAccess,
                            $saRootLocations[$siteSiteAccess],
                        ],
                        [
                            'content.tree_root.location_id',
                            null,
                            $gerSiteAccess,
                            $saRootLocations[$gerSiteAccess],
                        ],
                    ]
                )
            );

        $location = new Location(['id' => $gerSiteAccessLocationId]);

        $this->urlAliasService
            ->expects(self::once())
            ->method('listLocationAliases')
            ->with($location, false, null, null, ['ger-DE'])
            ->willReturn(
                [
                    new URLAlias(
                        ['path' => $gerRootLocationAlias = '/ger-folder'],
                    ),
                ],
            );

        $this->locationService
            ->expects(self::once())
            ->method('loadLocation')
            ->with($gerSiteAccessLocationId, ['ger-DE'])
            ->willReturn($location);

        $this->urlAliasService
            ->expects(self::once())
            ->method('reverseLookup')
            ->with($location, null, false, ['ger-DE'])
            ->willReturn($treeRootUrlAliases[$location->id]);

        $this->urlAliasGenerator->setSiteAccess(
            new SiteAccess(
                $gerSiteAccess,
                'default',
            )
        );

        self::assertSame(
            $gerRootLocationAlias,
            $this->urlAliasGenerator->doGenerate($location, $parameters)
        );
    }

    public function testDoGenerateNoUrlAlias(): void
    {
        $location = new Location(['id' => 123, 'contentInfo' => new ContentInfo(['id' => 456])]);
        $uri = "/content/location/$location->id";
        $this->urlAliasService
            ->expects(self::once())
            ->method('listLocationAliases')
            ->with($location, false)
            ->will(self::returnValue([]));
        $this->router
            ->expects(self::once())
            ->method('generate')
            ->with(
                UrlAliasGenerator::INTERNAL_CONTENT_VIEW_ROUTE,
                ['contentId' => $location->contentId, 'locationId' => $location->id]
            )
            ->will(self::returnValue($uri));

        self::assertSame($uri, $this->urlAliasGenerator->doGenerate($location, []));
    }

    /**
     * @dataProvider providerTestDoGenerateRootLocation
     */
    public function testDoGenerateRootLocation(URLAlias $urlAlias, bool $isOutsideAndNotExcluded, string $expected, string $pathPrefix): void
    {
        $excludedPrefixes = ['/products', '/shared'];
        $rootLocationId = 456;
        $this->urlAliasGenerator->setRootLocationId($rootLocationId);
        $this->urlAliasGenerator->setExcludedUriPrefixes($excludedPrefixes);
        $location = new Location(['id' => 123]);

        $rootLocation = new Location(['id' => $rootLocationId]);
        $rootUrlAlias = new URLAlias(['path' => $pathPrefix]);
        $this->locationService
            ->expects(self::once())
            ->method('loadLocation')
            ->with($rootLocationId)
            ->will(self::returnValue($rootLocation));
        $this->urlAliasService
            ->expects(self::once())
            ->method('reverseLookup')
            ->with($rootLocation)
            ->will(self::returnValue($rootUrlAlias));

        $this->urlAliasService
            ->expects(self::once())
            ->method('listLocationAliases')
            ->with($location, false)
            ->will(self::returnValue([$urlAlias]));

        if ($isOutsideAndNotExcluded) {
            $this->logger
                ->expects(self::once())
                ->method('warning');
        }

        self::assertSame($expected, $this->urlAliasGenerator->doGenerate($location, []));
    }

    public function providerTestDoGenerateRootLocation(): array
    {
        return [
            [
                new URLAlias(['path' => '/my/root-folder/foo/bar']),
                false,
                '/foo/bar',
                '/my/root-folder',
            ],
            [
                new URLAlias(['path' => '/my/root-folder/something']),
                false,
                '/something',
                '/my/root-folder',
            ],
            [
                new URLAlias(['path' => '/my/root-folder']),
                false,
                '/',
                '/my/root-folder',
            ],
            [
                new URLAlias(['path' => '/foo/bar']),
                false,
                '/foo/bar',
                '/',
            ],
            [
                new URLAlias(['path' => '/something']),
                false,
                '/something',
                '/',
            ],
            [
                new URLAlias(['path' => '/']),
                false,
                '/',
                '/',
            ],
            [
                new URLAlias(['path' => '/outside/tree/foo/bar']),
                true,
                '/outside/tree/foo/bar',
                '/my/root-folder',
            ],
            [
                new URLAlias(['path' => '/products/ibexa-dxp']),
                false,
                '/products/ibexa-dxp',
                '/my/root-folder',
            ],
            [
                new URLAlias(['path' => '/shared/some-content']),
                false,
                '/shared/some-content',
                '/my/root-folder',
            ],
            [
                new URLAlias(['path' => '/products/ibexa-dxp']),
                false,
                '/products/ibexa-dxp',
                '/prod',
            ],
        ];
    }

    protected function getPermissionResolverMock(): MockObject
    {
        $configResolverMock = $this->createMock(ConfigResolverInterface::class);
        $configResolverMock
            ->method('getParameter')
            ->with('anonymous_user_id')
            ->willReturn(10);

        return $this
            ->getMockBuilder(PermissionResolver::class)
            ->setMethods(null)
            ->setConstructorArgs(
                [
                    $this->createMock(RoleDomainMapper::class),
                    $this->createMock(LimitationService::class),
                    $this->createMock(SPIUserHandler::class),
                    $configResolverMock,
                    [],
                ]
            )
            ->getMock();
    }
}
