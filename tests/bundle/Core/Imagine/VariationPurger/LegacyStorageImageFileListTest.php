<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Bundle\Core\Imagine\VariationPurger;

use Ibexa\Bundle\Core\Imagine\VariationPurger\ImageFileRowReader;
use Ibexa\Bundle\Core\Imagine\VariationPurger\LegacyStorageImageFileList;
use Ibexa\Contracts\Core\SiteAccess\ConfigResolverInterface;
use Ibexa\Core\IO\IOConfigProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Ibexa\Bundle\Core\Imagine\VariationPurger\LegacyStorageImageFileList
 */
final class LegacyStorageImageFileListTest extends TestCase
{
    protected ImageFileRowReader&MockObject $rowReaderMock;

    protected LegacyStorageImageFileList $fileList;

    protected function setUp(): void
    {
        $this->rowReaderMock = $this->createMock(ImageFileRowReader::class);
        $ioConfigResolverMock = $this->createMock(IOConfigProvider::class);
        $ioConfigResolverMock
            ->method('getLegacyUrlPrefix')
            ->willReturn('var/ibexa_demo_site/storage');
        $configResolverMock = $this->createMock(ConfigResolverInterface::class);
        $configResolverMock
            ->method('getParameter')
            ->with('image.published_images_dir')
            ->willReturn('images');

        $this->fileList = new LegacyStorageImageFileList(
            $this->rowReaderMock,
            $ioConfigResolverMock,
            $configResolverMock
        );
    }

    public function testIterator(): void
    {
        $expected = [
            'path/to/1st/image.jpg',
            'path/to/2nd/image.jpg',
        ];
        $this->configureRowReaderMock($expected);

        foreach ($this->fileList as $index => $file) {
            self::assertEquals($expected[$index], $file);
        }
    }

    /**
     * Tests that the iterator transforms the ibexa_image_file value into a binary file id.
     */
    public function testImageIdTransformation(): void
    {
        $this->configureRowReaderMock(['var/ibexa_demo_site/storage/images/path/to/1st/image.jpg']);
        foreach ($this->fileList as $file) {
            self::assertEquals('path/to/1st/image.jpg', $file);
        }
    }

    /**
     * @param string[] $fileList
     */
    private function configureRowReaderMock(array $fileList): void
    {
        $fileListCount = count($fileList);
        // iterator will try to invoke methods one more time to establish its end
        $expectedIteratorInvocationsCount = $fileListCount + 1;

        $index = 0;
        $this->rowReaderMock
            ->expects(self::exactly($expectedIteratorInvocationsCount))
            ->method('getRow')
            ->willReturnCallback(static function () use (&$index, $fileList): ?string {
                return $fileList[$index++] ?? null;
            });

        $this->rowReaderMock
            ->expects(self::exactly($expectedIteratorInvocationsCount))
            ->method('getCount')
            ->willReturn($fileListCount);
    }
}
