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
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[\PHPUnit\Framework\Attributes\CoversClass(\Ibexa\Bundle\Core\Imagine\VariationPurger\ImageFileVariationPurger::class)]
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
        $matcher = self::exactly(4);

        $this->pathGeneratorMock
            ->expects($matcher)
            ->method('getVariationPath')->willReturnCallback(function (...$parameters) use ($matcher) {
                if ($matcher->numberOfInvocations() === 1) {
                    $this->assertSame('path/to/1st/image.jpg', $parameters[0]);
                    $this->assertSame('large', $parameters[1]);
                }
                if ($matcher->numberOfInvocations() === 2) {
                    $this->assertSame('path/to/1st/image.jpg', $parameters[0]);
                    $this->assertSame('gallery', $parameters[1]);
                }
                if ($matcher->numberOfInvocations() === 3) {
                    $this->assertSame('path/to/2nd/image.png', $parameters[0]);
                    $this->assertSame('large', $parameters[1]);
                }
                if ($matcher->numberOfInvocations() === 4) {
                    $this->assertSame('path/to/2nd/image.png', $parameters[0]);
                    $this->assertSame('gallery', $parameters[1]);
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
            ->expects(self::once())
            ->method('getVariationPath')
            ->willReturn('path/to/file_large.png');

        $this->ioServiceMock
            ->expects(self::once())
            ->method('exists')
            ->willReturn(true);

        $this->ioServiceMock
            ->expects(self::once())
            ->method('loadBinaryFile')
            ->willReturn(new BinaryFile());

        $this->ioServiceMock
            ->expects(self::once())
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
            ->expects(self::once())
            ->method('getVariationPath')
            ->willReturn('path/to/file_large.png');

        $this->ioServiceMock
            ->expects(self::once())
            ->method('exists')
            ->willReturn(false);

        $this->ioServiceMock
            ->expects(self::never())
            ->method('loadBinaryFile');

        $this->ioServiceMock
            ->expects(self::never())
            ->method('deleteBinaryFile');

        $purger->purge(['large']);
    }

    /**
     * @param string[] $fileList
     */
    private function createPurger(array $fileList): ImageFileVariationPurger
    {
        $imageFileList = new class($fileList) extends ArrayIterator implements ImageFileList {
        };

        return new ImageFileVariationPurger($imageFileList, $this->ioServiceMock, $this->pathGeneratorMock);
    }
}
