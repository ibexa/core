<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Bundle\IO;

use DateTime;
use Ibexa\Bundle\IO\BinaryStreamResponse;
use Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException;
use Ibexa\Core\IO\IOServiceInterface;
use Ibexa\Core\IO\Values\BinaryFile;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @covers \Ibexa\Bundle\IO\BinaryStreamResponse
 */
final class BinaryStreamResponseTest extends TestCase
{
    /**
     * @throws InvalidArgumentException
     */
    public function testSendContent(): void
    {
        $ioServiceMock = $this->createMock(IOServiceInterface::class);
        $binaryStreamResponse = $this->prepareBinaryStreamResponse($ioServiceMock);

        file_put_contents('php://input', 'test data');
        $in = fopen('php://input', 'rb');

        $ioServiceMock
            ->expects(self::once())
            ->method('getFileInputStream')
            ->with($binaryStreamResponse->getFile())
            ->willReturn($in);

        $binaryStreamResponse->sendContent();
    }

    /**
     * @dataProvider provideUnsatisfiableRanges
     */
    public function testSendsEmptyBodyForUnsatisfiableRange(string $range): void
    {
        $request = new Request();
        $request->headers->set('Range', $range);

        $binaryStreamResponse = $this->createBinaryStreamResponse();
        $binaryStreamResponse->prepare($request);

        self::assertSame(Response::HTTP_REQUESTED_RANGE_NOT_SATISFIABLE, $binaryStreamResponse->getStatusCode());
        self::assertSame('bytes */5321', $binaryStreamResponse->headers->get('Content-Range'));
        self::assertSame('0', $binaryStreamResponse->headers->get('Content-Length'));

        $this->expectOutputString('');
        $binaryStreamResponse->sendContent();
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function provideUnsatisfiableRanges(): iterable
    {
        yield 'range ending past the end of file' => ['bytes=0-999999999'];
        yield 'range starting past the end of file' => ['bytes=6000-7000'];
    }

    public function testSendsEmptyBodyWhenNotModified(): void
    {
        $request = new Request();
        $request->headers->set('If-Modified-Since', 'Wed, 28 Oct 2026 10:00:00 GMT');

        $binaryStreamResponse = $this->createBinaryStreamResponse();
        $binaryStreamResponse->prepare($request);

        self::assertTrue($binaryStreamResponse->isNotModified($request));
        self::assertSame(Response::HTTP_NOT_MODIFIED, $binaryStreamResponse->getStatusCode());

        $this->expectOutputString('');
        $binaryStreamResponse->sendContent();
    }

    private function createBinaryStreamResponse(): BinaryStreamResponse
    {
        $ioServiceStub = self::createStub(IOServiceInterface::class);
        $ioServiceStub->method('getMimeType')->willReturn('image/jpeg');

        $binaryFile = new BinaryFile([
            'id' => 'foo.jpg',
            'size' => 5321,
            'mtime' => new DateTime('2026-10-28 10:00:00 UTC'),
        ]);

        return new BinaryStreamResponse($binaryFile, $ioServiceStub);
    }

    private function prepareBinaryStreamResponse(IOServiceInterface & MockObject $ioServiceMock): BinaryStreamResponse
    {
        $request = new Request();

        $binaryFile = new BinaryFile(['id' => 'foo.jpg', 'size' => 5321]);
        $binaryStreamResponse = new BinaryStreamResponse($binaryFile, $ioServiceMock);

        $ioServiceMock
            ->expects(self::once())
            ->method('getMimeType')
            ->with('foo.jpg')
            ->willReturn('image/jpeg');

        $binaryStreamResponse->prepare($request);

        return $binaryStreamResponse;
    }
}
