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
use PHPUnit\Framework\TestCase;

class FlysystemTest extends TestCase
{
    /** @var \Ibexa\Core\IO\IOBinarydataHandler|\PHPUnit\Framework\MockObject\MockObject */
    private IOBinarydataHandler $handler;

    /** @var \League\Flysystem\FilesystemOperator|\PHPUnit\Framework\MockObject\MockObject */
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
                $this->equalTo(['mimetype' => 'image/png', 'visibility' => 'public'])
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
     * @throws \Ibexa\Core\IO\Exception\BinaryFileNotFoundException
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
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException
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
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException
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
