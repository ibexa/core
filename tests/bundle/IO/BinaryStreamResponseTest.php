<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Bundle\IO;

use DateTime;
use Ibexa\Bundle\IO\BinaryStreamResponse;
use Ibexa\Core\IO\Exception\BinaryFileNotFoundException;
use Ibexa\Core\IO\Exception\IOException;
use Ibexa\Core\IO\IOServiceInterface;
use Ibexa\Core\IO\RangeAwareIOServiceInterface;
use Ibexa\Core\IO\Values\BinaryFile;
use Ibexa\Tests\Core\IO\Stream\NonSeekableStreamWrapper;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class BinaryStreamResponseTest extends TestCase
{
    private const DATA = '0123456789';

    private BinaryFile $file;

    protected function setUp(): void
    {
        $this->file = new BinaryFile([
            'id' => 'video/movie.mp4',
            'size' => strlen(self::DATA),
            'mtime' => new DateTime('@1700000000'),
        ]);
    }

    /**
     * @dataProvider provideForSendContentWithNonRangeAwareIOService
     */
    public function testSendContentWithNonRangeAwareIOService(
        ?string $rangeHeader,
        int $expectedStatus,
        string $expectedBody
    ): void {
        $ioService = $this->createMock(IOServiceInterface::class);
        $ioService
            ->expects(self::once())
            ->method('getFileInputStream')
            ->with($this->file)
            ->willReturn(NonSeekableStreamWrapper::open(self::DATA));

        $response = $this->createResponse($ioService, $rangeHeader);

        self::assertSame($expectedStatus, $response->getStatusCode());
        self::assertSame($expectedBody, $this->captureContent($response));
    }

    /**
     * @return iterable<string, array{?string, int, string}>
     */
    public function provideForSendContentWithNonRangeAwareIOService(): iterable
    {
        yield 'no range' => [null, Response::HTTP_OK, '0123456789'];
        yield 'closed range' => ['bytes=2-4', Response::HTTP_PARTIAL_CONTENT, '234'];
        yield 'open-ended range' => ['bytes=7-', Response::HTTP_PARTIAL_CONTENT, '789'];
        yield 'suffix range' => ['bytes=-3', Response::HTTP_PARTIAL_CONTENT, '789'];
    }

    /**
     * @dataProvider provideForSendContentWithRangeAwareIOService
     */
    public function testSendContentWithRangeAwareIOService(
        ?string $rangeHeader,
        int $expectedOffset,
        ?int $expectedLength,
        string $streamFromStorage,
        string $expectedBody
    ): void {
        $ioService = $this->createMock(RangeAwareIOServiceInterface::class);
        $ioService
            ->expects(self::once())
            ->method('getFileInputStreamRange')
            ->with($this->file, $expectedOffset, $expectedLength)
            ->willReturn(NonSeekableStreamWrapper::open($streamFromStorage));
        $ioService
            ->expects(self::never())
            ->method('getFileInputStream');

        $response = $this->createResponse($ioService, $rangeHeader);

        self::assertSame($expectedBody, $this->captureContent($response));
    }

    /**
     * Storage returns the stream already positioned at the offset, possibly with extra trailing bytes.
     *
     * @return iterable<string, array{?string, int, ?int, string, string}>
     */
    public function provideForSendContentWithRangeAwareIOService(): iterable
    {
        yield 'no range' => [null, 0, null, '0123456789', '0123456789'];
        yield 'closed range' => ['bytes=2-4', 2, 3, '23456789', '234'];
        yield 'open-ended range' => ['bytes=7-', 7, 3, '789', '789'];
        yield 'suffix range' => ['bytes=-3', 7, 3, '789', '789'];
        yield 'first byte only' => ['bytes=0-0', 0, 1, '0123456789', '0'];
    }

    public function testPrepareThrowsBeforeContentIsSentWhenStreamIsShorterThanMetadataSize(): void
    {
        $ioService = $this->createMock(IOServiceInterface::class);
        $ioService
            ->expects(self::once())
            ->method('getFileInputStream')
            ->with($this->file)
            ->willReturn(NonSeekableStreamWrapper::open('0123'));

        $this->expectException(IOException::class);

        $this->createResponse($ioService, 'bytes=6-8');
    }

    public function testPrepareThrowsBeforeContentIsSentWhenStorageFails(): void
    {
        $ioService = $this->createMock(RangeAwareIOServiceInterface::class);
        $ioService
            ->expects(self::once())
            ->method('getFileInputStreamRange')
            ->with($this->file, 6, 3)
            ->willThrowException(new BinaryFileNotFoundException('video/movie.mp4'));

        $this->expectException(BinaryFileNotFoundException::class);

        $this->createResponse($ioService, 'bytes=6-8');
    }

    public function testPrepareDoesNotOpenStreamForUnsatisfiableRange(): void
    {
        $ioService = $this->createMock(RangeAwareIOServiceInterface::class);
        $ioService
            ->expects(self::never())
            ->method('getFileInputStreamRange');

        $response = $this->createResponse($ioService, 'bytes=5-20');

        self::assertSame(Response::HTTP_REQUESTED_RANGE_NOT_SATISFIABLE, $response->getStatusCode());
        self::assertSame('bytes */10', $response->headers->get('Content-Range'));
        self::assertSame('0', $response->headers->get('Content-Length'));
        self::assertSame('', $this->captureContent($response));
    }

    public function testSendContentOpensStreamWhenResponseWasNotPrepared(): void
    {
        $ioService = $this->createMock(IOServiceInterface::class);
        $ioService
            ->expects(self::once())
            ->method('getFileInputStream')
            ->with($this->file)
            ->willReturn(NonSeekableStreamWrapper::open(self::DATA));

        $response = new BinaryStreamResponse($this->file, $ioService);

        self::assertSame(self::DATA, $this->captureContent($response));
    }

    public function testRepeatedPrepareForSameRangeDoesNotReopenStream(): void
    {
        $ioService = $this->createMock(RangeAwareIOServiceInterface::class);
        $ioService
            ->expects(self::once())
            ->method('getFileInputStreamRange')
            ->with($this->file, 7, 3)
            ->willReturn(NonSeekableStreamWrapper::open('789'));

        $response = $this->createResponse($ioService, 'bytes=7-');
        $response->prepare($this->createRequest('bytes=7-'));

        self::assertSame('789', $this->captureContent($response));
    }

    public function testRepeatedPrepareForDifferentRangeReopensStream(): void
    {
        $firstStream = NonSeekableStreamWrapper::open('789');
        $streams = [
            7 => $firstStream,
            2 => NonSeekableStreamWrapper::open('23456789'),
        ];
        $ioService = $this->createMock(RangeAwareIOServiceInterface::class);
        $ioService
            ->expects(self::exactly(2))
            ->method('getFileInputStreamRange')
            ->with($this->file, self::logicalOr(7, 2), 3)
            ->willReturnCallback(
                static fn (BinaryFile $file, int $offset) => $streams[$offset]
            );

        $response = $this->createResponse($ioService, 'bytes=7-');
        $response->prepare($this->createRequest('bytes=2-4'));

        self::assertFalse(is_resource($firstStream));
        self::assertSame('234', $this->captureContent($response));
    }

    public function testSendContentClosesStreamWhenResponseBecameNotModified(): void
    {
        $stream = NonSeekableStreamWrapper::open(self::DATA);
        $ioService = $this->createMock(IOServiceInterface::class);
        $ioService
            ->expects(self::once())
            ->method('getFileInputStream')
            ->with($this->file)
            ->willReturn($stream);

        $response = $this->createResponse($ioService, null);
        $response->setNotModified();

        self::assertSame('', $this->captureContent($response));
        self::assertFalse(is_resource($stream));
    }

    public function testDestructClosesUnsentStream(): void
    {
        $stream = NonSeekableStreamWrapper::open(self::DATA);
        $ioService = $this->createMock(IOServiceInterface::class);
        $ioService
            ->expects(self::once())
            ->method('getFileInputStream')
            ->with($this->file)
            ->willReturn($stream);

        $response = $this->createResponse($ioService, null);
        unset($response);

        self::assertFalse(is_resource($stream));
    }

    private function createRequest(?string $rangeHeader): Request
    {
        $server = $rangeHeader !== null ? ['HTTP_RANGE' => $rangeHeader] : [];

        return Request::create('/', 'GET', [], [], [], $server);
    }

    private function createResponse(
        IOServiceInterface $ioService,
        ?string $rangeHeader
    ): BinaryStreamResponse {
        $response = new BinaryStreamResponse($this->file, $ioService, 200, ['Content-Type' => 'video/mp4']);
        $response->prepare($this->createRequest($rangeHeader));

        return $response;
    }

    private function captureContent(BinaryStreamResponse $response): string
    {
        ob_start();
        try {
            $response->sendContent();
        } finally {
            $content = (string)ob_get_clean();
        }

        return $content;
    }
}
