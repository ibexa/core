<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Bundle\Core\Imagine;

use Ibexa\Bundle\Core\Imagine\BinaryLoader;
use Ibexa\Core\Base\Exceptions\NotFoundException;
use Ibexa\Core\IO\Exception\InvalidBinaryFileIdException;
use Ibexa\Core\IO\IOServiceInterface;
use Ibexa\Core\IO\Values\BinaryFile;
use Ibexa\Core\IO\Values\MissingBinaryFile;
use Liip\ImagineBundle\Exception\Binary\Loader\NotLoadableException;
use Liip\ImagineBundle\Model\Binary;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Mime\MimeTypes;

class BinaryLoaderTest extends TestCase
{
    private IOServiceInterface & MockObject $ioService;

    private BinaryLoader $binaryLoader;

    protected function setUp(): void
    {
        parent::setUp();
        $this->ioService = $this->createMock(IOServiceInterface::class);
        $this->binaryLoader = new BinaryLoader($this->ioService, new MimeTypes());
    }

    public function testFindNotFound(): void
    {
        $this->expectException(NotLoadableException::class);

        $path = 'something.jpg';
        $this->ioService
            ->expects(self::once())
            ->method('loadBinaryFile')
            ->with($path)
            ->will(self::throwException(new NotFoundException('foo', 'bar')));

        $this->binaryLoader->find($path);
    }

    public function testFindMissing(): void
    {
        $this->expectException(NotLoadableException::class);

        $path = 'something.jpg';
        $this->ioService
            ->expects(self::once())
            ->method('loadBinaryFile')
            ->with($path)
            ->will(self::returnValue(new MissingBinaryFile()));

        $this->binaryLoader->find($path);
    }

    public function testFindBadPathRoot(): void
    {
        $path = 'var/site/storage/images/1/2/3/123-name/name.png';
        $this->ioService
            ->expects(self::once())
            ->method('loadBinaryFile')
            ->with($path)
            ->will(self::throwException(new InvalidBinaryFileIdException($path)));

        try {
            $this->binaryLoader->find($path);
        } catch (NotLoadableException $e) {
            self::assertStringContainsString(
                "Suggested value: '1/2/3/123-name/name.png'",
                $e->getMessage()
            );
        }
    }

    public function testFind(): void
    {
        $path = 'something.jpg';
        $mimeType = 'image/jpeg';
        $content = 'some content';
        $binaryFile = new BinaryFile(['id' => $path]);
        $this->ioService
            ->method('loadBinaryFile')
            ->with($path)
            ->willReturn($binaryFile);

        $this->ioService
            ->method('getFileContents')
            ->with($binaryFile)
            ->willReturn($content);

        $this->ioService
            ->method('getMimeType')
            ->with($binaryFile->id)
            ->willReturn($mimeType);

        $expected = new Binary($content, $mimeType, 'jpg');
        self::assertEquals($expected, $this->binaryLoader->find($path));
    }
}
