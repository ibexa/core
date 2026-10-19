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

    private function createResponse(
        IOServiceInterface $ioService,
        ?string $rangeHeader
    ): BinaryStreamResponse {
        $server = $rangeHeader !== null ? ['HTTP_RANGE' => $rangeHeader] : [];
        $response = new BinaryStreamResponse($this->file, $ioService, 200, ['Content-Type' => 'video/mp4']);
        $response->prepare(Request::create('/', 'GET', [], [], [], $server));

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
