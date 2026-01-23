<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Core\MVC\Symfony\View\Builder;

use Ibexa\Contracts\Core\Repository\ContentService;
use Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException as APINotFoundException;
use Ibexa\Contracts\Core\Repository\PermissionResolver;
use Ibexa\Contracts\Core\Repository\Values\Content\ContentInfo;
use Ibexa\Core\Base\Exceptions\InvalidArgumentException;
use Ibexa\Core\Base\Exceptions\NotFoundException;
use Ibexa\Core\Base\Exceptions\UnauthorizedException;
use Ibexa\Core\Helper\ContentInfoLocationLoader;
use Ibexa\Core\MVC\Exception\HiddenLocationException;
use Ibexa\Core\MVC\Symfony\View\Builder\ContentViewBuilder;
use Ibexa\Core\MVC\Symfony\View\Configurator;
use Ibexa\Core\MVC\Symfony\View\ContentView;
use Ibexa\Core\MVC\Symfony\View\ParametersInjector;
use Ibexa\Core\Repository\Repository;
use Ibexa\Core\Repository\Values\Content\Content;
use Ibexa\Core\Repository\Values\Content\Location;
use Ibexa\Core\Repository\Values\Content\VersionInfo;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * @group mvc
 */
class ContentViewBuilderTest extends TestCase
{
    private const int EXAMPLE_LOCATION_ID = 743;

    /** @var \Ibexa\Contracts\Core\Repository\Repository|MockObject */
    private $repository;

    /** @var Configurator|MockObject */
    private $viewConfigurator;

    /** @var ParametersInjector|MockObject */
    private $parametersInjector;

    /** @var ContentInfoLocationLoader|MockObject */
    private $contentInfoLocationLoader;

    /** @var ContentViewBuilder|MockObject */
    private $contentViewBuilder;

    /** @var PermissionResolver|MockObject */
    private $permissionResolver;

    /** @var RequestStack|MockObject */
    private $requestStack;

    protected function setUp(): void
    {
        $this->repository = $this
            ->getMockBuilder(Repository::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'sudo',
                'getPermissionResolver',
                'getLocationService',
                'getContentService',
            ])
            ->getMock();
        $this->viewConfigurator = $this->getMockBuilder(Configurator::class)->getMock();
        $this->parametersInjector = $this->getMockBuilder(ParametersInjector::class)->getMock();
        $this->contentInfoLocationLoader = $this->getMockBuilder(ContentInfoLocationLoader::class)->getMock();
        $this->permissionResolver = $this->getMockBuilder(PermissionResolver::class)->getMock();
        $this->requestStack = $this->getMockBuilder(RequestStack::class)->getMock();
        $this->repository
            ->expects(self::any())
            ->method('getPermissionResolver')
            ->willReturn($this->permissionResolver);

        $this->contentViewBuilder = new ContentViewBuilder(
            $this->repository,
            $this->viewConfigurator,
            $this->parametersInjector,
            $this->requestStack,
            $this->contentInfoLocationLoader
        );
    }

    public function testMatches(): void
    {
        self::assertTrue($this->contentViewBuilder->matches('ibexa_content:55'));
        self::assertFalse($this->contentViewBuilder->matches('dummy_value'));
    }

    public function testBuildViewWithoutLocationIdAndContentId(): void
    {
        $parameters = [
            'viewType' => 'full',
            '_controller' => 'ibexa_content:viewContent',
        ];

        $this->expectException(InvalidArgumentException::class);

        $this->contentViewBuilder->buildView($parameters);
    }

    public function testBuildViewWithInvalidLocationId(): void
    {
        $parameters = [
            'viewType' => 'full',
            '_controller' => 'ibexa_content:viewContent',
            'locationId' => 865,
        ];

        $this->repository
            ->expects(self::once())
            ->method('sudo')
            ->willThrowException(new NotFoundException('location', 865));

        $this->expectException(APINotFoundException::class);

        $this->contentViewBuilder->buildView($parameters);
    }

    public function testBuildViewWithHiddenLocation(): void
    {
        $parameters = [
            'viewType' => 'full',
            '_controller' => 'ibexa_content:viewContent',
            'locationId' => 2,
        ];

        $location = new Location(['invisible' => true]);

        $this->repository
            ->expects(self::once())
            ->method('sudo')
            ->willReturn($location);

        $this->expectException(HiddenLocationException::class);

        $this->contentViewBuilder->buildView($parameters);
    }

    public function testBuildViewWithoutContentReadPermission(): void
    {
        $location = new Location(
            [
                'id' => self::EXAMPLE_LOCATION_ID,
                'invisible' => false,
                'content' => new Content([
                    'versionInfo' => new VersionInfo([
                        'id' => 2,
                        'contentInfo' => new ContentInfo(['id' => 1]),
                    ]),
                ]),
            ]
        );

        $parameters = [
            'viewType' => 'full',
            '_controller' => 'ibexa_content:viewContent',
            'locationId' => 2,
        ];

        // It's call for LocationService::loadLocation()
        $this->repository
            ->expects(self::once())
            ->method('sudo')
            ->willReturn($location);

        $this->permissionResolver
            ->expects(self::any())
            ->method('canUser')
            ->willReturn(false);

        $this->expectException(UnauthorizedException::class);

        $this->contentViewBuilder->buildView($parameters);
    }

    public function testBuildEmbedViewWithoutContentViewEmbedPermission(): void
    {
        $location = new Location(
            [
                'id' => self::EXAMPLE_LOCATION_ID,
                'invisible' => false,
                'contentInfo' => new ContentInfo([
                    'id' => 120,
                ]),
                'content' => new Content([
                    'versionInfo' => new VersionInfo([
                        'contentInfo' => new ContentInfo([
                            'id' => 91,
                        ]),
                    ]),
                ]),
            ]
        );

        $parameters = [
            'viewType' => 'embed',
            '_controller' => 'ibexa_content:viewContent',
            'locationId' => 2,
        ];

        // It's call for LocationService::loadLocation()
        $this->repository
            ->expects(self::once())
            ->method('sudo')
            ->willReturn($location);

        $this->permissionResolver
            ->expects(self::exactly(2))
            ->method('canUser')
            ->willReturn(false);

        $this->expectException(UnauthorizedException::class);

        $this->contentViewBuilder->buildView($parameters);
    }

    public function testBuildEmbedViewWithNullMainRequest(): void
    {
        $contentId = 120;
        $content = new Content([
            'versionInfo' => new VersionInfo([
                'contentInfo' => new ContentInfo(['id' => $contentId]),
                'status' => VersionInfo::STATUS_PUBLISHED,
            ]),
        ]);

        $parameters = [
            'viewType' => 'embed',
            '_controller' => 'ibexa_content::embedAction',
            'contentId' => $contentId,
        ];

        $this->requestStack
            ->expects(self::once())
            ->method('getMainRequest')
            ->willReturn(null);

        $this->repository
            ->expects(self::once())
            ->method('sudo')
            ->willReturn($content);

        $this->permissionResolver
            ->expects(self::exactly(3))
            ->method('canUser')
            ->willReturn(true);

        $this->contentViewBuilder->buildView($parameters);
    }

    public function testBuildEmbedViewWithNotNullMainRequest(): void
    {
        $contentId = 120;
        $languageCode = 'ger-DE';

        $content = new Content([
            'versionInfo' => new VersionInfo([
                'contentInfo' => new ContentInfo(['id' => $contentId]),
                'status' => VersionInfo::STATUS_PUBLISHED,
            ]),
        ]);

        $parameters = [
            'viewType' => 'embed',
            '_controller' => 'ibexa_content::embedAction',
            'contentId' => $contentId,
        ];

        $attributes = $this->createMock(ParameterBag::class);
        $attributes
            ->expects(self::once())
            ->method('get')
            ->with('languageCode')
            ->willReturn($languageCode);

        $request = new Request();
        $reflectionClass = new ReflectionClass($request);
        $reflectionProperty = $reflectionClass->getProperty('attributes');
        $reflectionProperty->setValue($request, $attributes);

        $this->requestStack
            ->expects(self::once())
            ->method('getMainRequest')
            ->willReturn($request);

        $this->repository
            ->expects(self::once())
            ->method('sudo')
            ->willReturn($content);

        $this->permissionResolver
            ->expects(self::exactly(3))
            ->method('canUser')
            ->willReturn(true);

        $this->contentViewBuilder->buildView($parameters);
    }

    public function testBuildViewWithContentWhichDoesNotBelongToLocation(): void
    {
        $location = new Location(
            [
                'invisible' => false,
                'contentInfo' => new ContentInfo([
                    'id' => 120,
                ]),
                'content' => new Content([
                    'versionInfo' => new VersionInfo([
                        'contentInfo' => new ContentInfo([
                            'id' => 91,
                        ]),
                    ]),
                ]),
            ]
        );

        $parameters = [
            'viewType' => 'full',
            '_controller' => 'ibexa_content:viewContent',
            'locationId' => 2,
        ];

        // It's call for LocationService::loadLocation()
        $this->repository
            ->expects(self::once())
            ->method('sudo')
            ->willReturn($location);

        $this->permissionResolver
            ->expects(self::once())
            ->method('canUser')
            ->willReturn(true);

        $this->expectException(InvalidArgumentException::class);

        $this->contentViewBuilder->buildView($parameters);
    }

    public function testBuildViewWithTranslatedContentWithoutLocation(): void
    {
        $contentInfo = new ContentInfo(['id' => 120, 'mainLanguageCode' => 'eng-GB']);
        $content = new Content([
            'versionInfo' => new VersionInfo([
                'contentInfo' => $contentInfo,
            ]),
        ]);

        $parameters = [
            'viewType' => 'full',
            '_controller' => 'ibexa_content:viewContent',
            'contentId' => 120,
            'languageCode' => 'eng-GB',
        ];

        $contentServiceMock = $this
            ->getMockBuilder(ContentService::class)
            ->disableOriginalConstructor()
            ->getMock();

        $contentServiceMock
            ->method('loadContent')
            ->with(120, ['eng-GB'])
            ->willReturn($content);

        // No call for LocationService::loadLocation()
        $this->repository
            ->expects(self::never())
            ->method('sudo');

        $this->repository
            ->method('getContentService')
            ->willReturn($contentServiceMock);

        $expectedView = new ContentView(null, [], 'full');
        $expectedView->setContent($content);

        self::assertEquals($expectedView, $this->contentViewBuilder->buildView($parameters));
    }

    public function testBuildView(): void
    {
        $contentInfo = new ContentInfo(['id' => 120]);
        $content = new Content([
            'versionInfo' => new VersionInfo([
                'contentInfo' => $contentInfo,
            ]),
        ]);
        $location = new Location(
            [
                'invisible' => false,
                'contentInfo' => $contentInfo,
                'content' => $content,
            ]
        );

        $expectedView = new ContentView(null, [], 'full');
        $expectedView->setLocation($location);
        $expectedView->setContent($content);

        $parameters = [
            'viewType' => 'full',
            '_controller' => 'ibexa_content::viewAction',
            'locationId' => 2,
        ];

        $this->repository
            ->expects(self::once())
            ->method('sudo')
            ->willReturn($location);

        $this->permissionResolver
            ->expects(self::once())
            ->method('canUser')
            ->willReturn(true);

        self::assertEquals($expectedView, $this->contentViewBuilder->buildView($parameters));
    }

    /**
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\UnauthorizedException
     */
    public function testBuildViewInsertsDoNotGenerateEmbedUrlParameter(): void
    {
        $contentInfo = new ContentInfo(['id' => 120]);
        $content = new Content(
            [
                'versionInfo' => new VersionInfo(
                    [
                        'contentInfo' => $contentInfo,
                        'status' => VersionInfo::STATUS_PUBLISHED,
                    ]
                ),
            ]
        );
        $parameters = ['viewType' => 'embed', 'contentId' => 120, '_controller' => null];

        $this->repository
            ->expects(self::once())
            ->method('sudo')
            ->willReturn($content);

        $this->permissionResolver
            ->method('canUser')
            ->willReturnMap(
                [
                    ['content', 'read', $contentInfo, [], false],
                    ['content', 'view_embed', $contentInfo, [], true],
                    ['content', 'view_embed', $contentInfo, true],
                    ['content', 'read', $contentInfo, false],
                ]
            );

        $this
            ->parametersInjector
            ->method('injectViewParameters')
            ->with(
                self::isInstanceOf(ContentView::class),
                array_merge(
                    $parameters,
                    // invocation expectation:
                    ['params' => ['objectParameters' => ['doNotGenerateEmbedUrl' => true]]]
                )
            );

        $this->contentViewBuilder->buildView(
            $parameters
        );
    }
}
