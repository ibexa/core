<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Bundle\Core\Imagine\VariationPurger;

use ArrayIterator;
use Ibexa\Bundle\Core\Imagine\VariationPurger\ImageFileList;
use Ibexa\Bundle\Core\Imagine\VariationPurger\ImageFileVariationPurger;
use Ibexa\Contracts\Core\Variation\VariationPathGenerator;
use Ibexa\Core\IO\IOServiceInterface;
use Ibexa\Core\IO\Values\BinaryFile;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[CoversClass(ImageFileVariationPurger::class)]
final class ImageFileVariationPurgerTest extends TestCase
{
    protected IOServiceInterface & MockObject $ioServiceMock;

    protected VariationPathGenerator & MockObject $pathGeneratorMock;

    protected ImageFileVariationPurger $purger;

    protected function setUp(): void
    {
        $this->ioServiceMock = $this->createMock(IOServiceInterface::class);
        $this->pathGeneratorMock = $this->createMock(VariationPathGenerator::class);
    }

    public function testIteratesOverItems(): void
    {
        $purger = $this->createPurger(
            [
                'path/to/1st/image.jpg',
                'path/to/2nd/image.png',
            ]
        );
        $matcher = $this->exactly(4);

        $this->pathGeneratorMock
            ->expects($matcher)
            ->method('getVariationPath')->willReturnCallback(static function (...$parameters) use ($matcher): string {
                if ($matcher->numberOfInvocations() === 1) {
                    self::assertSame('path/to/1st/image.jpg', $parameters[0]);
                    self::assertSame('large', $parameters[1]);
                }
                if ($matcher->numberOfInvocations() === 2) {
                    self::assertSame('path/to/1st/image.jpg', $parameters[0]);
                    self::assertSame('gallery', $parameters[1]);
                }
                if ($matcher->numberOfInvocations() === 3) {
                    self::assertSame('path/to/2nd/image.png', $parameters[0]);
                    self::assertSame('large', $parameters[1]);
                }
                if ($matcher->numberOfInvocations() === 4) {
                    self::assertSame('path/to/2nd/image.png', $parameters[0]);
                    self::assertSame('gallery', $parameters[1]);
                }

                return '';
            });

        $purger->purge(['large', 'gallery']);
    }

    public function testPurgesExistingItem(): void
    {
        $purger = $this->createPurger(
            ['path/to/file.png']
        );

        $this->pathGeneratorMock
            ->expects($this->once())
            ->method('getVariationPath')
            ->willReturn('path/to/file_large.png');

        $this->ioServiceMock
            ->expects($this->once())
            ->method('exists')
            ->willReturn(true);

        $this->ioServiceMock
            ->expects($this->once())
            ->method('loadBinaryFile')
            ->willReturn(new BinaryFile());

        $this->ioServiceMock
            ->expects($this->once())
            ->method('deleteBinaryFile')
            ->with(self::isInstanceOf(BinaryFile::class));

        $purger->purge(['large']);
    }

    public function testDoesNotPurgeNotExistingItem(): void
    {
        $purger = $this->createPurger(
            ['path/to/file.png']
        );

        $this->pathGeneratorMock
            ->expects($this->once())
            ->method('getVariationPath')
            ->willReturn('path/to/file_large.png');

        $this->ioServiceMock
            ->expects($this->once())
            ->method('exists')
            ->willReturn(false);

        $this->ioServiceMock
            ->expects($this->never())
            ->method('loadBinaryFile');

        $this->ioServiceMock
            ->expects($this->never())
            ->method('deleteBinaryFile');

        $purger->purge(['large']);
    }

    /**
     * @param string[] $fileList
     */
    private function createPurger(array $fileList): ImageFileVariationPurger
    {
        $imageFileList = new class($fileList) extends ArrayIterator implements ImageFileList {};

        return new ImageFileVariationPurger($imageFileList, $this->ioServiceMock, $this->pathGeneratorMock);
    }
}
