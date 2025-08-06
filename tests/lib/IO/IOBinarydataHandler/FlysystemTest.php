<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Core\IO\IOBinarydataHandler;

use Ibexa\Contracts\Core\IO\BinaryFileCreateStruct as SPIBinaryFileCreateStruct;
use Ibexa\Core\IO\Exception\BinaryFileNotFoundException;
use Ibexa\Core\IO\IOBinarydataHandler;
use Ibexa\Core\IO\IOBinarydataHandler\Flysystem;
use League\Flysystem\FilesystemOperator;
use League\Flysystem\UnableToDeleteFile;
use League\Flysystem\UnableToReadFile;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class FlysystemTest extends TestCase
{
    private IOBinarydataHandler $handler;

    private FilesystemOperator & MockObject $filesystem;

    protected function setUp(): void
    {
        $this->filesystem = $this->createMock(FilesystemOperator::class);
        $this->handler = new Flysystem($this->filesystem);
    }

    public function testCreate(): void
    {
        $stream = fopen('php://memory', 'rb');
        self::assertNotFalse($stream, 'Failed to create in-memory stream');
        $spiBinaryFileCreateStruct = new SPIBinaryFileCreateStruct();
        $spiBinaryFileCreateStruct->id = 'prefix/my/file.png';
        $spiBinaryFileCreateStruct->mimeType = 'image/png';
        $spiBinaryFileCreateStruct->size = 123;
        $spiBinaryFileCreateStruct->mtime = new \DateTime('@1307155200');
        $spiBinaryFileCreateStruct->setInputStream($stream);

        $this->filesystem
            ->expects(self::once())
            ->method('writeStream')
            ->with(
                self::equalTo($spiBinaryFileCreateStruct->id),
                self::equalTo($stream),
                self::equalTo(
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
            ->expects(self::once())
            ->method('delete')
            ->with('prefix/my/file.png');

        $this->handler->delete('prefix/my/file.png');
    }

    public function testDeleteNotFound(): void
    {
        // Note: technically Flysystem's v2+ Local Adapter silently skips non-existent file
        $filePath = 'prefix/my/file.png';
        $this->filesystem
            ->expects(self::once())
            ->method('delete')
            ->with($filePath)
            ->willThrowException(UnableToDeleteFile::atLocation($filePath));

        $this->expectException(BinaryFileNotFoundException::class);

        $this->handler->delete($filePath);
    }

    /**
     * @throws \Ibexa\Core\IO\Exception\BinaryFileNotFoundException
     */
    public function testGetContents(): void
    {
        $filePath = 'prefix/my/file.png';
        $fileContents = 'This is my contents';
        $this->filesystem
            ->expects(self::once())
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
            ->expects(self::once())
            ->method('read')
            ->with($filePath)
            ->willThrowException(UnableToReadFile::fromLocation($filePath));

        $this->expectException(BinaryFileNotFoundException::class);
        $this->handler->getContents($filePath);
    }

    /**
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException
     */
    public function testGetResource(): void
    {
        $resource = fopen('php://temp', 'rb');

        $filePath = 'prefix/my/file.png';
        $this->filesystem
            ->expects(self::once())
            ->method('readStream')
            ->with($filePath)
            ->willReturn($resource);

        self::assertEquals(
            $resource,
            $this->handler->getResource($filePath)
        );
    }

    /**
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException
     */
    public function testGetResourceNotFound(): void
    {
        $filePath = 'prefix/my/file.png';
        $this->filesystem
            ->expects(self::once())
            ->method('readStream')
            ->with($filePath)
            ->willThrowException(UnableToReadFile::fromLocation($filePath));

        $this->expectException(BinaryFileNotFoundException::class);
        $this->handler->getResource($filePath);
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
            ->expects(self::once())
            ->method('deleteDirectory')
            ->with('some/path');

        $this->handler->deleteDirectory('some/path');
    }
}
