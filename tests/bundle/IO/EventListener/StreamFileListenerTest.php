<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Bundle\IO\EventListener;

use DateTime;
use Ibexa\Bundle\IO\BinaryStreamResponse;
use Ibexa\Bundle\IO\EventListener\StreamFileListener;
use Ibexa\Contracts\Core\Repository\Exceptions\Exception;
use Ibexa\Core\IO\IOConfigProvider;
use Ibexa\Core\IO\IOServiceInterface;
use Ibexa\Core\IO\Values\BinaryFile;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * @covers \Ibexa\Bundle\IO\EventListener\StreamFileListener
 */
final class StreamFileListenerTest extends TestCase
{
    private StreamFileListener $eventListener;

    private IOServiceInterface & MockObject $ioServiceMock;

    private IOConfigProvider & MockObject $ioConfigResolverMock;

    protected function setUp(): void
    {
        $this->ioServiceMock = $this->createMock(IOServiceInterface::class);

        $this->ioConfigResolverMock = $this->createMock(IOConfigProvider::class);

        $this->eventListener = new StreamFileListener($this->ioServiceMock, $this->ioConfigResolverMock);
    }

    /**
     * @throws Exception
     */
    public function testDoesNotRespondToNonIoUri(): void
    {
        $request = $this->createRequest('/Not-an-image');
        $event = $this->createEvent($request);

        $this->configureIoUrlPrefix('var/test/storage');
        $this->ioServiceMock
            ->expects(self::never())
            ->method('loadBinaryFileByUri');

        $this->eventListener->onKernelRequest($event);

        self::assertNull($event->getResponse());
    }

    /**
     * @throws Exception
     */
    public function testDoesNotRespondToNoIoRequest(): void
    {
        $request = $this->createRequest('/Not-an-image', 'bar.fr');
        $event = $this->createEvent($request);

        $this->configureIoUrlPrefix('http://foo.com/var/test/storage');
        $this->ioServiceMock
            ->expects(self::never())
            ->method('loadBinaryFileByUri');

        $this->eventListener->onKernelRequest($event);

        self::assertNull($event->getResponse());
    }

    /**
     * @throws Exception
     */
    public function testRespondsToIoUri(): void
    {
        $uri = $binaryFileUri = '/var/test/storage/images/image.png';
        $urlPrefix = ltrim($uri, '/');
        $request = $this->createRequest($uri);

        $this->assertOnKernelRequestResponse($request, $urlPrefix, $binaryFileUri);
    }

    /**
     * @throws Exception
     */
    public function testRespondsToIoRequest(): void
    {
        $uri = '/var/test/storage/images/image.png';
        $host = 'phoenix-rises.fm';
        $urlPrefix = "http://$host/var/test/storage";
        $request = $this->createRequest($uri, $host);

        $this->assertOnKernelRequestResponse($request, $urlPrefix, sprintf('http://%s%s', $host, $uri));
    }

    private function configureIoUrlPrefix(string $urlPrefix): void
    {
        $this->ioConfigResolverMock
            ->method('getUrlPrefix')
            ->willReturn($urlPrefix);
    }

    protected function createRequest(
        string $semanticPath,
        string $host = 'localhost'
    ): Request {
        $request = Request::create(sprintf('http://%s%s', $host, $semanticPath));
        $request->attributes->set('semanticPathinfo', $semanticPath);

        return $request;
    }

    protected function createEvent(Request $request): RequestEvent
    {
        return new RequestEvent(
            $this->createMock(HttpKernelInterface::class),
            $request,
            HttpKernelInterface::MAIN_REQUEST
        );
    }

    /**
     * @throws Exception
     */
    private function assertOnKernelRequestResponse(
        Request $request,
        string $urlPrefix,
        string $binaryFileUri
    ): void {
        $this->configureIoUrlPrefix($urlPrefix);

        $event = $this->createEvent($request);

        $binaryFile = new BinaryFile(['mtime' => new DateTime()]);

        $this->ioServiceMock
            ->expects(self::once())
            ->method('loadBinaryFileByUri')
            ->with($binaryFileUri)
            ->willReturn($binaryFile)
        ;

        $this->eventListener->onKernelRequest($event);

        self::assertTrue($event->hasResponse());
        $expectedResponse = new BinaryStreamResponse($binaryFile, $this->ioServiceMock);
        $response = $event->getResponse();
        $date = $response?->getDate();
        self::assertNotNull($date);
        // since symfony/symfony v3.2.7 Response sets Date header if not explicitly set
        // @see https://github.com/symfony/symfony/commit/e3d90db74773406fb8fdf07f36cb8ced4d187f62
        $expectedResponse->setDate($date);
        self::assertEquals(
            $expectedResponse,
            $response
        );
    }
}
