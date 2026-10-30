<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Core\IO\IOBinarydataHandler;

use Ibexa\Contracts\Core\IO\BinaryFileCreateStruct as SPIBinaryFileCreateStruct;
use Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException;
use Ibexa\Core\IO\Exception\BinaryFileNotFoundException;
use Ibexa\Core\IO\Flysystem\RangeReader\RangeReaderInterface;
use Ibexa\Core\IO\IOBinarydataHandler;
use Ibexa\Core\IO\IOBinarydataHandler\Flysystem;
use League\Flysystem\FilesystemOperator;
use League\Flysystem\UnableToDeleteFile;
use League\Flysystem\UnableToReadFile;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class FlysystemTest extends TestCase
{
    /** @var IOBinarydataHandler|MockObject */
    private IOBinarydataHandler $handler;

    /** @var FilesystemOperator|MockObject */
    private FilesystemOperator $filesystem;

    protected function setUp(): void
    {
        $this->filesystem = $this->createMock(FilesystemOperator::class);
        $this->handler = new Flysystem($this->filesystem);
    }

    public function testCreate(): void
    {
        $stream = fopen('php://memory', 'rb');
        $spiBinaryFileCreateStruct = new SPIBinaryFileCreateStruct();
        $spiBinaryFileCreateStruct->id = 'prefix/my/file.png';
        $spiBinaryFileCreateStruct->mimeType = 'image/png';
        $spiBinaryFileCreateStruct->size = 123;
        $spiBinaryFileCreateStruct->mtime = 1307155200;
        $spiBinaryFileCreateStruct->setInputStream($stream);

        $this->filesystem
            ->expects($this->once())
            ->method('writeStream')
            ->with(
                $this->equalTo($spiBinaryFileCreateStruct->id),
                $this->equalTo($stream),
                $this->equalTo(
                    [
                        'mimetype' => 'image/png',
                        'visibility' => 'public',
                        'directory_visibility' => 'public',
                    ]
                )
            );

        $this->handler->create($spiBinaryFileCreateStruct);
    }

    public function testDelete()
    {
        $this->filesystem
            ->expects($this->once())
            ->method('delete')
            ->with('prefix/my/file.png');

        $this->handler->delete('prefix/my/file.png');
    }

    public function testDeleteNotFound(): void
    {
        // Note: technically Flysystem's v2+ Local Adapter silently skips non-existent file
        $filePath = 'prefix/my/file.png';
        $this->filesystem
            ->expects($this->once())
            ->method('delete')
            ->with($filePath)
            ->willThrowException(UnableToDeleteFile::atLocation($filePath));

        $this->expectException(BinaryFileNotFoundException::class);

        $this->handler->delete($filePath);
    }

    /**
     * @throws BinaryFileNotFoundException
     */
    public function testGetContents(): void
    {
        $filePath = 'prefix/my/file.png';
        $fileContents = 'This is my contents';
        $this->filesystem
            ->expects($this->once())
            ->method('read')
            ->with($filePath)
            ->willReturn($fileContents);

        self::assertEquals(
            $fileContents,
            $this->handler->getContents($filePath)
        );
    }

    public function testGetContentsNotFound(): void
    {
        $filePath = 'prefix/my/file.png';
        $this->filesystem
            ->expects($this->once())
            ->method('read')
            ->with($filePath)
            ->willThrowException(UnableToReadFile::fromLocation($filePath));

        $this->expectException(BinaryFileNotFoundException::class);
        $this->handler->getContents($filePath);
    }

    /**
     * @throws NotFoundException
     */
    public function testGetResource(): void
    {
        $resource = fopen('php://temp', 'rb');

        $filePath = 'prefix/my/file.png';
        $this->filesystem
            ->expects($this->once())
            ->method('readStream')
            ->with($filePath)
            ->willReturn($resource);

        self::assertEquals(
            $resource,
            $this->handler->getResource($filePath)
        );
    }

    /**
     * @throws NotFoundException
     */
    public function testGetResourceNotFound(): void
    {
        $filePath = 'prefix/my/file.png';
        $this->filesystem
            ->expects($this->once())
            ->method('readStream')
            ->with($filePath)
            ->willThrowException(UnableToReadFile::fromLocation($filePath));

        $this->expectException(BinaryFileNotFoundException::class);
        $this->handler->getResource($filePath);
    }

    public function testGetResourceRangeReturnsNullWithoutRangeReader(): void
    {
        $this->filesystem
            ->expects(self::never())
            ->method('readStream');

        self::assertNull((new Flysystem($this->filesystem))->getResourceRange('prefix/my/file.mp4', 100, 50));
    }

    /**
     * @dataProvider provideForGetResourceRangeUsesRangeReader
     *
     * @param resource|null $readerResult
     */
    public function testGetResourceRangeUsesRangeReader($readerResult): void
    {
        $rangeReader = $this->createMock(RangeReaderInterface::class);
        $rangeReader
            ->expects(self::once())
            ->method('readStreamRange')
            ->with('prefix/my/file.mp4', 100, 50)
            ->willReturn($readerResult);
        $this->filesystem
            ->expects(self::never())
            ->method('readStream');

        $handler = new Flysystem($this->filesystem, null, $rangeReader);

        self::assertSame($readerResult, $handler->getResourceRange('prefix/my/file.mp4', 100, 50));
    }

    /**
     * @return iterable<string, array{resource|null}>
     */
    public function provideForGetResourceRangeUsesRangeReader(): iterable
    {
        $stream = fopen('php://memory', 'rb');
        self::assertIsResource($stream);

        yield 'reader serves range' => [$stream];
        yield 'reader cannot serve range' => [null];
    }

    public function testGetResourceRangeThrowsNotFoundOnReaderFailure(): void
    {
        $rangeReader = $this->createMock(RangeReaderInterface::class);
        $rangeReader
            ->expects(self::once())
            ->method('readStreamRange')
            ->with('prefix/my/file.mp4', 100, null)
            ->willThrowException(UnableToReadFile::fromLocation('prefix/my/file.mp4'));

        $handler = new Flysystem($this->filesystem, null, $rangeReader);

        $this->expectException(BinaryFileNotFoundException::class);

        $handler->getResourceRange('prefix/my/file.mp4', 100);
    }

    public function testGetUri(): void
    {
        self::assertEquals(
            '/prefix/my/file.png',
            $this->handler->getUri('prefix/my/file.png')
        );
    }

    public function testDeleteDirectory(): void
    {
        $this->filesystem
            ->expects($this->once())
            ->method('deleteDirectory')
            ->with('some/path');

        $this->handler->deleteDirectory('some/path');
    }
}

class_alias(FlysystemTest::class, 'eZ\Publish\Core\IO\Tests\IOBinarydataHandler\FlysystemTest');
