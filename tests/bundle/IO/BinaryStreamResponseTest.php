<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Bundle\IO;

use Ibexa\Bundle\IO\BinaryStreamResponse;
use Ibexa\Core\IO\IOServiceInterface;
use Ibexa\Core\IO\Values\BinaryFile;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

/**
 * @covers \Ibexa\Bundle\IO\BinaryStreamResponse
 */
final class BinaryStreamResponseTest extends TestCase
{
    /**
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException
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

    private function prepareBinaryStreamResponse(IOServiceInterface & MockObject $ioServiceMock): BinaryStreamResponse
    {
        $request = new Request();

        $binaryFile = new BinaryFile(['id' => 'foo.jpg', 'size' => 5321]);
        $binaryStreamResponse = new BinaryStreamResponse($binaryFile, $ioServiceMock);

        $ioServiceMock->expects(self::once())->method('getMimeType')->with('foo.jpg')->willReturn('image/jpeg');

        $binaryStreamResponse->prepare($request);

        return $binaryStreamResponse;
    }
}
