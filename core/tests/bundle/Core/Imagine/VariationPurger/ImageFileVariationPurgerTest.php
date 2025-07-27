<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Bundle\Core\Imagine\VariationPurger;

use ArrayIterator;
use Ibexa\Bundle\Core\Imagine\VariationPurger\ImageFileVariationPurger;
use Ibexa\Contracts\Core\Variation\VariationPathGenerator;
use Ibexa\Core\IO\IOServiceInterface;
use Ibexa\Core\IO\Values\BinaryFile;
use PHPUnit\Framework\TestCase;

class ImageFileVariationPurgerTest extends TestCase
{
    /** @var \Ibexa\Core\IO\IOServiceInterface|\PHPUnit\Framework\MockObject\MockObject */
    protected $ioServiceMock;

    /** @var \Ibexa\Contracts\Core\Variation\VariationPathGenerator|\PHPUnit\Framework\MockObject\MockObject */
    protected $pathGeneratorMock;

    /** @var \Ibexa\Bundle\Core\Imagine\VariationPurger\ImageFileVariationPurger */
    protected $purger;

    protected function setUp(): void
    {
        $this->ioServiceMock = $this->createMock(IOServiceInterface::class);
        $this->pathGeneratorMock = $this->createMock(VariationPathGenerator::class);
    }

    public function testIteratesOverItems()
    {
        $purger = $this->createPurger(
            [
                'path/to/1st/image.jpg',
                'path/to/2nd/image.png',
            ]
        );

        $this->pathGeneratorMock
            ->expects(self::exactly(4))
            ->method('getVariationPath')
            ->withConsecutive(
                ['path/to/1st/image.jpg', 'large'],
                ['path/to/1st/image.jpg', 'gallery'],
                ['path/to/2nd/image.png', 'large'],
                ['path/to/2nd/image.png', 'gallery']
            );

        $purger->purge(['large', 'gallery']);
    }

    public function testPurgesExistingItem()
    {
        $purger = $this->createPurger(
            ['path/to/file.png']
        );

        $this->pathGeneratorMock
            ->expects(self::once())
            ->method('getVariationPath')
            ->will(self::returnValue('path/to/file_large.png'));

        $this->ioServiceMock
            ->expects(self::once())
            ->method('exists')
            ->will(self::returnValue(true));

        $this->ioServiceMock
            ->expects(self::once())
            ->method('loadBinaryFile')
            ->will(self::returnValue(new BinaryFile()));

        $this->ioServiceMock
            ->expects(self::once())
            ->method('deleteBinaryFile')
            ->with(self::isInstanceOf(BinaryFile::class));

        $purger->purge(['large']);
    }

    public function testDoesNotPurgeNotExistingItem()
    {
        $purger = $this->createPurger(
            ['path/to/file.png']
        );

        $this->pathGeneratorMock
            ->expects(self::once())
            ->method('getVariationPath')
            ->will(self::returnValue('path/to/file_large.png'));

        $this->ioServiceMock
            ->expects(self::once())
            ->method('exists')
            ->will(self::returnValue(false));

        $this->ioServiceMock
            ->expects(self::never())
            ->method('loadBinaryFile');

        $this->ioServiceMock
            ->expects(self::never())
            ->method('deleteBinaryFile');

        $purger->purge(['large']);
    }

    private function createPurger(array $fileList)
    {
        return new ImageFileVariationPurger(new ArrayIterator($fileList), $this->ioServiceMock, $this->pathGeneratorMock);
    }
}
