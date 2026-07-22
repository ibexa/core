<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Integration\Core\MVC\Symfony\Controller\Content;

use DateTime;
use Ibexa\Bundle\IO\BinaryStreamResponse;
use Ibexa\Contracts\Core\Repository\ContentService;
use Ibexa\Contracts\Core\Repository\Values\Content\ContentInfo;
use Ibexa\Contracts\Core\Repository\Values\Content\Field;
use Ibexa\Contracts\Core\Test\IbexaKernelTestCase;
use Ibexa\Core\FieldType\BinaryFile\Value as BinaryFileValue;
use Ibexa\Core\Helper\TranslationHelper;
use Ibexa\Core\IO\IOServiceInterface;
use Ibexa\Core\IO\Values\BinaryFile;
use Ibexa\Core\MVC\Symfony\Controller\Content\DownloadController;
use Ibexa\Core\Repository\Values\Content\Content;
use Ibexa\Core\Repository\Values\Content\VersionInfo;
use Ibexa\Tests\Integration\Core\MVC\Symfony\InternalRoutingTestKernel;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Controller\ArgumentResolver;
use Symfony\Component\HttpKernel\Controller\ControllerResolverInterface;
use Symfony\Component\HttpKernel\EventListener\RouterListener;
use Symfony\Component\HttpKernel\HttpKernel;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Routing\Generator\UrlGenerator;
use Symfony\Component\Routing\Matcher\UrlMatcher;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * @group integration
 *
 * @covers \Ibexa\Core\MVC\Symfony\Controller\Content\DownloadController
 */
final class DownloadControllerRequestFlowTest extends IbexaKernelTestCase
{
    private const FILENAME = 'Q1 report #1 + 100%.jpg';

    /** @var \Ibexa\Contracts\Core\Repository\ContentService&\PHPUnit\Framework\MockObject\MockObject */
    private ContentService $contentService;

    /** @var \Ibexa\Core\IO\IOServiceInterface&\PHPUnit\Framework\MockObject\MockObject */
    private IOServiceInterface $ioService;

    /** @var \Ibexa\Core\Helper\TranslationHelper&\PHPUnit\Framework\MockObject\MockObject */
    private TranslationHelper $translationHelper;

    protected static function getKernelClass(): string
    {
        return InternalRoutingTestKernel::class;
    }

    protected function setUp(): void
    {
        parent::setUp();

        self::bootKernel();

        $this->contentService = $this->createMock(ContentService::class);
        $this->ioService = $this->createMock(IOServiceInterface::class);
        $this->translationHelper = $this->createMock(TranslationHelper::class);
    }

    public function testDownloadsFileWithUrlEncodedFilename(): void
    {
        $routes = $this->createRouteCollection();
        $context = new RequestContext();
        $url = (new UrlGenerator($routes, $context))->generate(
            'ibexa.content.download',
            [
                'contentId' => 42,
                'fieldIdentifier' => 'file',
                'filename' => self::FILENAME,
                'inLanguage' => 'eng-GB',
            ]
        );

        self::assertStringContainsString('Q1%20report%20%231%20+%20100%25.jpg', $url);

        $content = $this->createContent();
        $field = $content->getField('file', 'eng-GB');
        self::assertInstanceOf(Field::class, $field);

        $binaryFile = new BinaryFile([
            'id' => 'binary-file-id',
            'mtime' => new DateTime(),
            'size' => 123,
            'uri' => 'binary-file-uri',
        ]);

        $this->contentService
            ->expects(self::once())
            ->method('loadContent')
            ->with(42)
            ->willReturn($content);
        $this->translationHelper
            ->expects(self::once())
            ->method('getTranslatedField')
            ->with($content, 'file', 'eng-GB')
            ->willReturn($field);
        $this->ioService
            ->expects(self::once())
            ->method('loadBinaryFile')
            ->with('binary-file-id')
            ->willReturn($binaryFile);

        $this->configureDownloadController($routes);

        $response = $this->createHttpKernel($routes, $context)->handle(
            Request::create($url),
            HttpKernelInterface::MAIN_REQUEST,
            false
        );

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertInstanceOf(BinaryStreamResponse::class, $response);
    }

    private function configureDownloadController(RouteCollection $routes): void
    {
        $route = $routes->get('ibexa.content.download');
        self::assertNotNull($route);
        $route->setDefault('_controller', [$this->createController(), 'downloadBinaryFileAction']);
    }

    private function createController(): DownloadController
    {
        return new DownloadController(
            $this->contentService,
            $this->ioService,
            $this->translationHelper
        );
    }

    private function createHttpKernel(RouteCollection $routes, RequestContext $context): HttpKernel
    {
        $requestStack = new RequestStack();
        $dispatcher = new EventDispatcher();
        $dispatcher->addSubscriber(new RouterListener(
            new UrlMatcher($routes, $context),
            $requestStack,
            $context
        ));

        $controllerResolver = self::getContainer()->get('controller_resolver');
        self::assertInstanceOf(ControllerResolverInterface::class, $controllerResolver);

        return new HttpKernel(
            $dispatcher,
            $controllerResolver,
            $requestStack,
            new ArgumentResolver()
        );
    }

    private function createRouteCollection(): RouteCollection
    {
        $routes = new RouteCollection();
        $routes->add('ibexa.content.download', new Route(
            '/content/download/{contentId}/{fieldIdentifier}/{filename}',
            [],
            ['contentId' => '\d+']
        ));

        return $routes;
    }

    private function createContent(): Content
    {
        return new Content([
            'internalFields' => [
                new Field([
                    'fieldDefIdentifier' => 'file',
                    'languageCode' => 'eng-GB',
                    'value' => new BinaryFileValue([
                        'id' => 'binary-file-id',
                        'fileName' => self::FILENAME,
                    ]),
                ]),
            ],
            'versionInfo' => new VersionInfo([
                'contentInfo' => new ContentInfo([
                    'id' => 42,
                    'mainLanguageCode' => 'eng-GB',
                    'name' => 'Test content',
                    'status' => ContentInfo::STATUS_PUBLISHED,
                ]),
            ]),
        ]);
    }
}
