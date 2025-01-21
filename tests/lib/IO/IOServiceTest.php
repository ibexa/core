<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Core\IO;

use Closure;
use Ibexa\Contracts\Core\IO\BinaryFile as SPIBinaryFile;
use Ibexa\Contracts\Core\IO\BinaryFileCreateStruct as SPIBinaryFileCreateStruct;
use Ibexa\Contracts\Core\IO\MimeTypeDetector;
use Ibexa\Core\IO\Exception\BinaryFileNotFoundException;
use Ibexa\Core\IO\IOBinarydataHandler;
use Ibexa\Core\IO\IOMetadataHandler;
use Ibexa\Core\IO\IOService;
use Ibexa\Core\IO\Values\BinaryFile;
use Ibexa\Core\IO\Values\BinaryFileCreateStruct;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Ibexa\Core\IO\IOService
 */
class IOServiceTest extends TestCase
{
    public const string PREFIX = 'test-prefix';
    protected const string BINARY_FILE_ID_MY_PATH = 'my/path.png';
    private const string MIME_TYPE_TEXT_PHP = 'text/x-php';

    protected IOService $ioService;

    protected IOMetadataHandler & MockObject $metadataHandlerMock;

    protected IOBinarydataHandler & MockObject $binarydataHandlerMock;

    protected MimeTypeDetector & MockObject $mimeTypeDetectorMock;

    protected function setUp(): void
    {
        parent::setUp();

        $this->binarydataHandlerMock = $this->createMock(IOBinarydataHandler::class);
        $this->metadataHandlerMock = $this->createMock(IOMetadataHandler::class);
        $this->mimeTypeDetectorMock = $this->createMock(MimeTypeDetector::class);

        $this->ioService = new IOService(
            $this->metadataHandlerMock,
            $this->binarydataHandlerMock,
            $this->mimeTypeDetectorMock,
            ['prefix' => self::PREFIX]
        );
    }

    public function testNewBinaryCreateStructFromLocalFile(): BinaryFileCreateStruct
    {
        $file = __FILE__;

        $this->mimeTypeDetectorMock
            ->expects(self::once())
            ->method('getFromPath')
            ->with(self::equalTo($file))
            ->willReturn(self::MIME_TYPE_TEXT_PHP);

        $binaryCreateStruct = $this->getIoService()->newBinaryCreateStructFromLocalFile(
            $file
        );

        self::assertInstanceOf(BinaryFileCreateStruct::class, $binaryCreateStruct);
        self::assertNull($binaryCreateStruct->id);
        self::assertIsResource($binaryCreateStruct->inputStream);
        self::assertEquals(filesize(__FILE__), $binaryCreateStruct->size);
        self::assertEquals(self::MIME_TYPE_TEXT_PHP, $binaryCreateStruct->mimeType);

        return $binaryCreateStruct;
    }

    /**
     * @depends testNewBinaryCreateStructFromLocalFile
     */
    public function testCreateBinaryFile(BinaryFileCreateStruct $createStruct): BinaryFile
    {
        $createStruct->id = 'my/path.php';
        $id = $this->getPrefixedUri($createStruct->id);

        $spiBinaryFile = new SPIBinaryFile();
        $spiBinaryFile->id = $id;
        $spiBinaryFile->uri = $id;
        $filesize = filesize(__FILE__);
        self::assertNotFalse($filesize);
        $spiBinaryFile->size = $filesize;

        $this->binarydataHandlerMock
            ->expects(self::once())
            ->method('create')
            ->with(
                self::callback(
                    static function ($subject) use ($id): bool {
                        if (!$subject instanceof SPIBinaryFileCreateStruct) {
                            return false;
                        }

                        return $subject->id === $id;
                    }
                )
            );

        $this->metadataHandlerMock
            ->expects(self::once())
            ->method('create')
            ->with(self::callback($this->getSPIBinaryFileCreateStructCallback($id)))
            ->willReturn($spiBinaryFile);

        $binaryFile = $this->ioService->createBinaryFile($createStruct);
        self::assertInstanceOf(BinaryFile::class, $binaryFile);
        self::assertEquals($createStruct->id, $binaryFile->id);
        self::assertEquals($createStruct->size, $binaryFile->size);

        return $binaryFile;
    }

    public function testLoadBinaryFile(): BinaryFile
    {
        $id = self::BINARY_FILE_ID_MY_PATH;
        $spiId = $this->getPrefixedUri($id);
        $spiBinaryFile = new SPIBinaryFile();
        $spiBinaryFile->id = $spiId;
        $spiBinaryFile->size = 12345;
        $spiBinaryFile->uri = $spiId;

        $this->metadataHandlerMock
            ->expects(self::once())
            ->method('load')
            ->with($spiId)
            ->willReturn($spiBinaryFile);

        $binaryFile = $this->getIoService()->loadBinaryFile($id);
        self::assertEquals($id, $binaryFile->id);

        return $binaryFile;
    }

    /**
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\Exception
     */
    public function testLoadBinaryFileNoMetadataUri(): BinaryFile
    {
        $id = self::BINARY_FILE_ID_MY_PATH;
        $spiId = $this->getPrefixedUri($id);
        $prefixedId = $this->mockGettingPrefixedUriFromDataHandler($id);
        $spiBinaryFile = new SPIBinaryFile();
        $spiBinaryFile->id = $spiId;
        $spiBinaryFile->size = 12345;

        $this->metadataHandlerMock
            ->expects(self::once())
            ->method('load')
            ->with($spiId)
            ->willReturn($spiBinaryFile);

        $binaryFile = $this->getIoService()->loadBinaryFile($id);

        $expectedBinaryFile = new BinaryFile(
            [
                'id' => $id,
                'size' => 12345, 'uri' => $prefixedId, 'mtime' => null,
            ]
        );

        self::assertEquals($expectedBinaryFile, $binaryFile);

        return $binaryFile;
    }

    /**
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException
     */
    public function testLoadBinaryFileNotFound(): BinaryFile
    {
        $this->expectException(BinaryFileNotFoundException::class);

        return $this->loadBinaryFileNotFound();
    }

    public function testLoadBinaryFileByUri(): BinaryFile
    {
        $id = self::BINARY_FILE_ID_MY_PATH;
        $spiId = $this->getPrefixedUri($id);
        $spiBinaryFile = new SPIBinaryFile();
        $spiBinaryFile->id = $spiId;
        $spiBinaryFile->size = 12345;
        $spiBinaryFile->uri = $spiId;

        $this->binarydataHandlerMock
            ->expects(self::once())
            ->method('getIdFromUri')
            ->with($spiId)
            ->willReturn($spiId);

        $this->metadataHandlerMock
            ->expects(self::once())
            ->method('load')
            ->with($spiId)
            ->willReturn($spiBinaryFile);

        $binaryFile = $this->getIoService()->loadBinaryFileByUri($spiId);
        self::assertEquals($id, $binaryFile->id);

        return $binaryFile;
    }

    /**
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException
     */
    public function testLoadBinaryFileByUriNotFound(): BinaryFile
    {
        $this->expectException(BinaryFileNotFoundException::class);

        return $this->loadBinaryFileByUriNotFound();
    }

    /**
     * @depends testLoadBinaryFile
     */
    public function testGetFileContents(BinaryFile $binaryFile): void
    {
        $expectedContents = file_get_contents(__FILE__);

        $this->binarydataHandlerMock
            ->expects(self::once())
            ->method('getContents')
            ->with(self::equalTo($this->getPrefixedUri($binaryFile->id)))
            ->willReturn($expectedContents);

        self::assertEquals(
            $expectedContents,
            $this->getIoService()->getFileContents($binaryFile)
        );
    }

    /**
     * @depends testCreateBinaryFile
     */
    public function testExists(BinaryFile $binaryFile): void
    {
        $this->metadataHandlerMock
            ->expects(self::once())
            ->method('exists')
            ->with(self::equalTo($this->getPrefixedUri($binaryFile->id)))
            ->willReturn(true);

        self::assertTrue(
            $this->getIoService()->exists(
                $binaryFile->id
            )
        );
    }

    public function testExistsNot(): void
    {
        $this->metadataHandlerMock
            ->expects(self::once())
            ->method('exists')
            ->with(self::equalTo($this->getPrefixedUri(__METHOD__)))
            ->willReturn(false);

        self::assertFalse(
            $this->getIoService()->exists(
                __METHOD__
            )
        );
    }

    /**
     * @depends testCreateBinaryFile
     */
    public function testGetMimeType(BinaryFile $binaryFile): void
    {
        $this->metadataHandlerMock
            ->expects(self::once())
            ->method('getMimeType')
            ->with(self::equalTo($this->getPrefixedUri($binaryFile->id)))
            ->willReturn(self::MIME_TYPE_TEXT_PHP);

        self::assertEquals(
            'text/x-php',
            $this->getIoService()->getMimeType(
                $binaryFile->id
            )
        );
    }

    /**
     * @depends testCreateBinaryFile
     */
    public function testDeleteBinaryFile(BinaryFile $binaryFile): void
    {
        $this->metadataHandlerMock
            ->expects(self::once())
            ->method('delete')
            ->with(self::equalTo($this->getPrefixedUri($binaryFile->id)));

        $this->binarydataHandlerMock
            ->expects(self::once())
            ->method('delete')
            ->with(self::equalTo($this->getPrefixedUri($binaryFile->id)));

        $this->getIoService()->deleteBinaryFile($binaryFile);
    }

    public function testDeleteDirectory(): void
    {
        $id = 'some/directory';
        $spiId = $this->getPrefixedUri($id);

        $this->binarydataHandlerMock
            ->expects(self::once())
            ->method('deleteDirectory')
            ->with($spiId);

        $this->metadataHandlerMock
            ->expects(self::once())
            ->method('deleteDirectory')
            ->with($spiId);

        $this->getIoService()->deleteDirectory('some/directory');
    }

    /**
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException
     */
    public function testDeleteBinaryFileNotFound(): void
    {
        $this->expectException(BinaryFileNotFoundException::class);

        $this->deleteBinaryFileNotFound();
    }

    public function getPrefixedUri(string $uri): string
    {
        return self::PREFIX . '/' . $uri;
    }

    protected function getIOService(): IOService
    {
        return $this->ioService;
    }

    /**
     * Asserts that the given $ioCreateStruct is of the right type and that id matches the expected value.
     */
    private function getSPIBinaryFileCreateStructCallback(string $spiId): Closure
    {
        return static function ($subject) use ($spiId): bool {
            if (!$subject instanceof SPIBinaryFileCreateStruct) {
                return false;
            }

            return $subject->id === $spiId;
        };
    }

    /**
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException
     */
    protected function loadBinaryFileNotFound(): BinaryFile
    {
        $id = 'id.ext';
        $prefixedUri = $this->getPrefixedUri($id);

        $this->metadataHandlerMock
            ->expects(self::once())
            ->method('load')
            ->with($prefixedUri)
            ->willThrowException(new BinaryFileNotFoundException($prefixedUri));

        return $this->getIoService()->loadBinaryFile($id);
    }

    /**
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException
     */
    protected function deleteBinaryFileNotFound(): void
    {
        $binaryFile = new BinaryFile(
            ['id' => __METHOD__, 'uri' => '/test-prefix/' . __METHOD__]
        );

        $prefixedId = $this->getPrefixedUri($binaryFile->id);
        $this->metadataHandlerMock
            ->expects(self::once())
            ->method('delete')
            ->with(self::equalTo($prefixedId))
            ->willThrowException(new BinaryFileNotFoundException($prefixedId));

        $this->getIoService()->deleteBinaryFile($binaryFile);
    }

    /**
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException
     */
    protected function loadBinaryFileByUriNotFound(): BinaryFile
    {
        $id = self::BINARY_FILE_ID_MY_PATH;
        $spiId = $this->getPrefixedUri($id);

        $this->binarydataHandlerMock
            ->expects(self::once())
            ->method('getIdFromUri')
            ->with($spiId)
            ->willReturn($spiId);

        $this->metadataHandlerMock
            ->expects(self::once())
            ->method('load')
            ->with($spiId)
            ->willThrowException(new BinaryFileNotFoundException($spiId));

        return $this->getIoService()->loadBinaryFileByUri($spiId);
    }

    protected function mockGettingPrefixedUriFromDataHandler(string $uri): string
    {
        $prefixedUri = $this->getPrefixedUri($uri);
        $this->binarydataHandlerMock
            ->expects(self::once())
            ->method('getUri')
            ->with($prefixedUri)
            ->willReturn($prefixedUri);

        return $prefixedUri;
    }
}
