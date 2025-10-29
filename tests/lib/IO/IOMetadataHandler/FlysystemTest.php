<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Core\IO\IOMetadataHandler;

use DateTime;
use Ibexa\Contracts\Core\IO\BinaryFile as SPIBinaryFile;
use Ibexa\Contracts\Core\IO\BinaryFileCreateStruct as SPIBinaryFileCreateStruct;
use Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException;
use Ibexa\Core\IO\Exception\BinaryFileNotFoundException;
use Ibexa\Core\IO\IOMetadataHandler;
use Ibexa\Core\IO\IOMetadataHandler\Flysystem;
use League\Flysystem\FilesystemOperator;
use League\Flysystem\UnableToRetrieveMetadata;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class FlysystemTest extends TestCase
{
    /** @var IOMetadataHandler|MockObject */
    private $handler;

    /** @var FilesystemOperator|MockObject */
    private $filesystem;

    protected function setUp(): void
    {
        $this->filesystem = $this->createMock(FilesystemOperator::class);
        $this->handler = new Flysystem($this->filesystem);
    }

    /**
     * @throws NotFoundException
     */
    public function testCreate(): void
    {
        // good example of bad responsibilities... since create also loads, we test the same thing twice
        $spiCreateStruct = new SPIBinaryFileCreateStruct();
        $spiCreateStruct->id = 'prefix/my/file.png';
        $spiCreateStruct->size = 123;
        $spiCreateStruct->mtime = new DateTime('@1307155200');

        $expectedSpiBinaryFile = new SPIBinaryFile();
        $expectedSpiBinaryFile->id = 'prefix/my/file.png';
        $expectedSpiBinaryFile->size = 123;
        $expectedSpiBinaryFile->mtime = new DateTime('@1307155200');

        $this->filesystem
            ->expects(self::once())
            ->method('fileSize')
            ->with($spiCreateStruct->id)
            ->willReturn(123);

        $this->filesystem
            ->expects(self::once())
            ->method('lastModified')
            ->with($spiCreateStruct->id)
            ->willReturn(1307155200);

        $spiBinaryFile = $this->handler->create($spiCreateStruct);

        self::assertInstanceOf(SPIBinaryFile::class, $spiBinaryFile);
        self::assertEquals($expectedSpiBinaryFile, $spiBinaryFile);
    }

    public function testDelete()
    {
        $this->filesystem->expects(self::never())->method('delete');
        $this->handler->delete('prefix/my/file.png');
    }

    public function testLoad(): void
    {
        $expectedSpiBinaryFile = new SPIBinaryFile();
        $filePath = 'prefix/my/file.png';
        $expectedSpiBinaryFile->id = $filePath;
        $expectedSpiBinaryFile->size = 123;
        $expectedSpiBinaryFile->mtime = new DateTime('@1307155200');

        $this->filesystem
            ->expects(self::once())
            ->method('fileSize')
            ->with($filePath)
            ->willReturn(123);

        $this->filesystem
            ->expects(self::once())
            ->method('lastModified')
            ->with($filePath)
            ->willReturn(1307155200);

        $spiBinaryFile = $this->handler->load($filePath);

        self::assertInstanceOf(SPIBinaryFile::class, $spiBinaryFile);
        self::assertEquals($expectedSpiBinaryFile, $spiBinaryFile);
    }

    public function testLoadNotFound(): void
    {
        $notExistentPath = 'prefix/my/file.png';
        $this->filesystem
            ->expects(self::once())
            ->method('fileSize')
            ->with($notExistentPath)
            ->willThrowException(UnableToRetrieveMetadata::fileSize($notExistentPath));

        $this->expectException(BinaryFileNotFoundException::class);

        $this->handler->load($notExistentPath);
    }

    /**
     * @dataProvider getDataForFileExists
     */
    public function testExists(
        string $filePath,
        bool $exists
    ): void {
        $this->filesystem
            ->expects(self::once())
            ->method('fileExists')
            ->with($filePath)
            // Note: test proper proxying of Flysystem call as this is a unit test
            ->willReturn($exists);

        self::assertSame($exists, $this->handler->exists($filePath));
    }

    public function getDataForFileExists(): iterable
    {
        $filePath = 'prefix/my/file.png';

        yield 'exists' => [$filePath, true];
        yield 'does not exist' => [$filePath, false];
    }

    public function testGetMimeType(): void
    {
        $fileName = 'file.txt';
        $this->filesystem
            ->expects(self::once())
            ->method('mimeType')
            ->with($fileName)
            // Note: test proper proxying of Flysystem call as this is a unit test
            ->willReturn('text/plain');

        self::assertEquals('text/plain', $this->handler->getMimeType($fileName));
    }

    public function testDeleteDirectory(): void
    {
        // test this actually doesn't call Flysystem's FilesystemOperator::deleteDirectory
        // (see \Ibexa\Core\IO\IOMetadataHandler\Flysystem::deleteDirectory for more details)
        $this->filesystem
            ->expects(self::never())
            ->method('deleteDirectory');

        $this->handler->deleteDirectory('some/path');
    }
}
