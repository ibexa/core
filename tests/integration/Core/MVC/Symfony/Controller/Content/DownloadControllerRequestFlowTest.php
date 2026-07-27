<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Integration\Core\MVC\Symfony\Controller\Content;

use Ibexa\Bundle\IO\BinaryStreamResponse;
use Ibexa\Contracts\Core\Repository\Values\Content\Field;
use Ibexa\Contracts\Core\Test\IbexaKernelTestCase;
use Ibexa\Tests\Core\MVC\Symfony\Controller\Controller\Content\DownloadControllerTestTrait;
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
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\RouterInterface;

/**
 * @group integration
 *
 * @covers \Ibexa\Core\MVC\Symfony\Controller\Content\DownloadController
 */
final class DownloadControllerRequestFlowTest extends IbexaKernelTestCase
{
    use DownloadControllerTestTrait;

    private const string FILENAME = 'Q1 report #1 + 100%.jpg';

    protected static function getKernelClass(): string
    {
        return InternalRoutingTestKernel::class;
    }

    protected function setUp(): void
    {
        parent::setUp();

        self::bootKernel();

        $this->initializeServiceMocks();
    }

    public function testDownloadsFileWithUrlEncodedFilename(): void
    {
        $routes = $this->getAppRouteCollection();
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

        $content = $this->createContent(self::FILENAME);
        $field = $content->getField('file', 'eng-GB');
        self::assertInstanceOf(Field::class, $field);

        $binaryFile = $this->createBinaryFile();

        $this->expectContentLoaded(42, $content);
        $this->expectTranslatedField($content, 'file', $field);
        $this->expectBinaryFileLoaded($binaryFile);

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

    private function getAppRouteCollection(): RouteCollection
    {
        $router = self::getContainer()->get('router.default');
        self::assertInstanceOf(RouterInterface::class, $router);

        return clone $router->getRouteCollection();
    }
}
