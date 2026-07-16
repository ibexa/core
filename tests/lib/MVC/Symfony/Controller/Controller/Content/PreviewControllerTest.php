<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Core\MVC\Symfony\Controller\Controller\Content;

use Ibexa\Contracts\Core\Repository\ContentService;
use Ibexa\Contracts\Core\Repository\Exceptions\Exception;
use Ibexa\Contracts\Core\Repository\LocationService;
use Ibexa\Contracts\Core\Repository\Values\Content\Content;
use Ibexa\Contracts\Core\Repository\Values\Content\Location;
use Ibexa\Core\Base\Exceptions\NotFoundException;
use Ibexa\Core\Base\Exceptions\UnauthorizedException;
use Ibexa\Core\Helper\ContentPreviewHelper;
use Ibexa\Core\Helper\PreviewLocationProvider;
use Ibexa\Core\MVC\Symfony\Controller\Content\PreviewController;
use Ibexa\Core\MVC\Symfony\SiteAccess;
use Ibexa\Core\MVC\Symfony\View\CustomLocationControllerChecker;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * @covers \Ibexa\Core\MVC\Symfony\Controller\Content\PreviewController
 */
final class PreviewControllerTest extends TestCase
{
    /** @var ContentService&MockObject */
    protected ContentService $contentService;

    /** @var LocationService&MockObject */
    protected LocationService $locationService;

    /** @var HttpKernelInterface&MockObject */
    protected HttpKernelInterface $httpKernel;

    /** @var ContentPreviewHelper&MockObject */
    protected ContentPreviewHelper $previewHelper;

    /** @var AuthorizationCheckerInterface&MockObject */
    protected AuthorizationCheckerInterface $authorizationChecker;

    /** @var PreviewLocationProvider&MockObject */
    protected PreviewLocationProvider $locationProvider;

    /** @var CustomLocationControllerChecker&MockObject */
    protected CustomLocationControllerChecker $controllerChecker;

    /** @var LoggerInterface&MockObject */
    private LoggerInterface $logger;

    protected function setUp(): void
    {
        parent::setUp();

        $this->contentService = $this->createMock(ContentService::class);
        $this->locationService = $this->createMock(LocationService::class);
        $this->httpKernel = $this->createMock(HttpKernelInterface::class);
        $this->previewHelper = $this->createMock(ContentPreviewHelper::class);
        $this->authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);
        $this->locationProvider = $this->createMock(PreviewLocationProvider::class);
        $this->controllerChecker = $this->createMock(CustomLocationControllerChecker::class);
        $this->logger = $this->createMock(LoggerInterface::class);
    }

    protected function getPreviewController(): PreviewController
    {
        return new PreviewController(
            $this->contentService,
            $this->locationService,
            $this->httpKernel,
            $this->previewHelper,
            $this->authorizationChecker,
            $this->locationProvider,
            $this->controllerChecker,
            false,
            $this->logger
        );
    }

    /**
     * @throws Exception
     */
    public function testPreviewUnauthorized(): void
    {
        $this->expectException(AccessDeniedException::class);

        $controller = $this->getPreviewController();
        $contentId = 123;
        $lang = 'eng-GB';
        $versionNo = 3;
        $this->contentService
            ->expects(self::once())
            ->method('loadContent')
            ->with($contentId, [$lang], $versionNo)
            ->willThrowException(new UnauthorizedException('foo', 'bar'))
        ;

        $controller->previewContentAction(new Request(), $contentId, $versionNo, $lang, 'test');
    }

    /**
     * @throws Exception
     */
    public function testPreviewCanUserFail(): void
    {
        $controller = $this->getPreviewController();
        $contentId = 123;
        $lang = 'eng-GB';
        $versionNo = 3;
        $content = $this->createMock(Content::class);

        $location = $this->createMock(Location::class);
        $this->locationProvider
            ->method('loadMainLocationByContent')
            ->with($content)
            ->willReturn($location)
        ;
        $this->contentService
            ->method('loadContent')
            ->with($contentId, [$lang], $versionNo)
            ->willReturn($content)
        ;

        $this->authorizationChecker->method('isGranted')->willReturn(false);

        $this->expectException(AccessDeniedException::class);

        $controller->previewContentAction(new Request(), $contentId, $versionNo, $lang, 'test');
    }

    public function testPreviewWithLogMessage(): void
    {
        $controller = $this->getPreviewController();
        $contentId = 123;
        $lang = 'eng-GB';
        $versionNo = 3;
        $content = $this->createMock(Content::class);

        $location = $this->createMock(Location::class);
        $location->method('__get')->with('id')->willReturn('42');

        $siteAccess = $this->createMock(SiteAccess::class);
        $this->locationProvider
            ->method('loadMainLocationByContent')
            ->with($content)
            ->willReturn($location)
        ;
        $this->contentService
            ->method('loadContent')
            ->with($contentId, [$lang], $versionNo)
            ->willReturn($content)
        ;

        $this->authorizationChecker->method('isGranted')->willReturn(true);
        $siteAccess->name = 'test';
        $this->previewHelper->method('getOriginalSiteAccess')->willReturn($siteAccess);
        $this->httpKernel->method('handle')->willThrowException(new NotFoundException('Foo Property', 'foobar'));

        $this->logger
            ->expects(self::once())
            ->method('warning')
            ->with('Location (42) not found or not available in requested language (eng-GB) when loading the preview page');

        $controller->previewContentAction(new Request(), $contentId, $versionNo, $lang, 'test');
    }

    /**
     * @return iterable<string, array{SiteAccess|null, int, string, int, int|null, string|null}>
     */
    public static function getDataForTestPreview(): iterable
    {
        yield 'with different SiteAccess, main Location' => [
            new SiteAccess('test', 'preview'),
            123, // contentId
            'eng-GB',
            3, // versionNo
            null, // secondary Location Id
            null,
        ];

        yield 'with default SiteAccess, main Location' => [
            null,
            234, // contentId
            'ger-DE',
            1, // versionNo
            null, // secondary Location Id
            null,
        ];

        yield 'with different SiteAccess, secondary Location' => [
            new SiteAccess('test', 'preview'),
            567, // contentId
            'eng-GB',
            11, // versionNo
            220, // secondary Location Id
            null,
        ];

        yield 'with default SiteAccess, secondary Location' => [
            null,
            234, // contentId
            'ger-DE',
            1, // versionNo
            221, // secondary Location Id
            null,
        ];

        yield 'with different SiteAccess and different viewType' => [
            new SiteAccess('test', 'preview'),
            789, // contentId
            'eng-GB',
            9, // versionNo
            null,
            'foo_view_type',
        ];
    }

    /**
     * @dataProvider getDataForTestPreview
     *
     * @throws Exception
     */
    public function testPreview(
        ?SiteAccess $previewSiteAccess,
        int $contentId,
        string $language,
        int $versionNo,
        ?int $locationId,
        ?string $viewType = null
    ): void {
        $content = $this->createMock(Content::class);
        $location = $this->getMockBuilder(Location::class)
             ->setConstructorArgs([['id' => $locationId ?? 456]])
             ->getMockForAbstractClass();

        if (null === $locationId) {
            $this->locationProvider->method('loadMainLocationByContent')->with($content)->willReturn($location);
        } else {
            $this->locationService->method('loadLocation')->with($locationId)->willReturn($location);
        }

        $this->contentService
            ->method('loadContent')
            ->with($contentId, [$language], $versionNo)
            ->willReturn($content);

        $this->authorizationChecker->method('isGranted')->willReturn(true);

        $originalSiteAccess = new SiteAccess('foo');

        $request = new Request();
        $request->attributes->set('semanticPathinfo', '/foo/bar');
        if (null !== $viewType) {
            $request->query->set('viewType', $viewType);
        }

        $this->configurePreviewHelper(
            $content,
            $location,
            $originalSiteAccess,
            $previewSiteAccess
        );

        $forwardRequestParameters = $this->getExpectedForwardRequestParameters(
            $location,
            $content,
            $previewSiteAccess ?? $originalSiteAccess,
            $language,
            $viewType
        );

        // the actual assertion happens here, checking if the forward request params are correct
        $this->httpKernel
            ->method('handle')
            ->with($request->duplicate(null, null, $forwardRequestParameters), HttpKernelInterface::SUB_REQUEST)
            ->willReturn(new Response())
        ;

        $controller = $this->getPreviewController();
        $controller->previewContentAction(
            $request,
            $contentId,
            $versionNo,
            $language,
            $previewSiteAccess !== null ? $previewSiteAccess->name : null,
            $locationId
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function getExpectedForwardRequestParameters(
        Location $location,
        Content $content,
        SiteAccess $previewSiteAccess,
        string $language,
        ?string $viewType
    ): array {
        return [
            '_controller' => 'ibexa_content::viewAction',
            '_route' => 'ibexa.content.view',
            '_route_params' => [
                'contentId' => $content->id,
                'locationId' => $location->id,
            ],
            'location' => $location,
            'content' => $content,
            'viewType' => $viewType ?? 'full',
            'layout' => true,
            'params' => [
                'content' => $content,
                'location' => $location,
                'isPreview' => true,
                'language' => $language,
            ],
            'siteaccess' => $previewSiteAccess,
            'semanticPathinfo' => '/foo/bar',
        ];
    }

    private function configurePreviewHelper(
        Content $content,
        Location $location,
        SiteAccess $originalSiteAccess,
        ?SiteAccess $previewSiteAccess = null
    ): void {
        $this->previewHelper
            ->expects(self::exactly(2))
            ->method('setPreviewActive')
            ->withConsecutive([true], [false])
        ;

        $this->previewHelper
            ->expects(self::once())
            ->method('setPreviewedContent')
            ->with($content)
        ;
        $this->previewHelper
            ->expects(self::once())
            ->method('setPreviewedLocation')
            ->with($location)
        ;
        $this->previewHelper
            ->expects(self::once())
            ->method('getOriginalSiteAccess')
            ->willReturn($originalSiteAccess)
        ;

        if ($previewSiteAccess !== null) {
            $this->previewHelper
                ->expects(self::once())
                ->method('changeConfigScope')
                ->with($previewSiteAccess->name)
                ->willReturn($previewSiteAccess)
            ;
        }

        $this->previewHelper
            ->expects(self::once())
            ->method('restoreConfigScope')
        ;
    }
}
