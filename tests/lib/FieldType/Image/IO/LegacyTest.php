<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Core\FieldType\Image\IO;

use Ibexa\Contracts\Core\SiteAccess\ConfigResolverInterface;
use Ibexa\Core\Base\Exceptions\InvalidArgumentException;
use Ibexa\Core\FieldType\Image\IO\Legacy as LegacyIOService;
use Ibexa\Core\FieldType\Image\IO\OptionsProvider;
use Ibexa\Core\IO\IOServiceInterface;
use Ibexa\Core\IO\Values\BinaryFile;
use Ibexa\Core\IO\Values\BinaryFileCreateStruct;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Ibexa\Core\FieldType\Image\IO\Legacy
 */
final class LegacyTest extends TestCase
{
    protected LegacyIOService $service;

    /**
     * Internal IOService instance for published images.
     */
    protected IOServiceInterface & MockObject $publishedIoServiceMock;

    /**
     * Internal IOService instance for draft images.
     */
    protected IOServiceInterface & MockObject $draftIoServiceMock;

    protected function setUp(): void
    {
        $this->publishedIoServiceMock = $this->createMock(IOServiceInterface::class);
        $this->draftIoServiceMock = $this->createMock(IOServiceInterface::class);
        $optionsProvider = new OptionsProvider($this->createConfigResolverMock());
        $this->service = new LegacyIOService(
            $this->publishedIoServiceMock,
            $this->draftIoServiceMock,
            $optionsProvider
        );
    }

    /**
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException
     */
    public function testNewBinaryCreateStructFromLocalFile(): void
    {
        $path = '/tmp/file.png';
        $struct = new BinaryFileCreateStruct();
        $this->publishedIoServiceMock
            ->expects(self::once())
            ->method('newBinaryCreateStructFromLocalFile')
            ->with($path)
            ->willReturn($struct);

        $this->draftIoServiceMock->expects(self::never())->method('newBinaryCreateStructFromLocalFile');

        self::assertEquals(
            $struct,
            $this->service->newBinaryCreateStructFromLocalFile($path)
        );
    }

    public function testExists(): void
    {
        $path = 'path/file.png';
        $this->publishedIoServiceMock
            ->expects(self::once())
            ->method('exists')
            ->with($path)
            ->willReturn(true);

        $this->draftIoServiceMock->expects(self::never())->method('exists');

        self::assertTrue(
            $this->service->exists($path)
        );
    }

    /**
     * Standard binary file, with regular id.
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\Exception
     */
    public function testLoadBinaryFile(): void
    {
        $id = 'path/file.jpg';
        $binaryFile = new BinaryFile(['id' => $id]);

        $this->publishedIoServiceMock
            ->expects(self::once())
            ->method('loadBinaryFile')
            ->with($id)
            ->willReturn($binaryFile);

        $this->draftIoServiceMock->expects(self::never())->method('loadBinaryFile');

        self::assertSame(
            $binaryFile,
            $this->service->loadBinaryFile($id)
        );
    }

    /**
     * Load from an internal draft binary file path.
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\Exception
     */
    public function testLoadBinaryFileDraftInternalPath(): void
    {
        $internalId = 'var/test/storage/images-versioned/path/file.jpg';
        $id = 'path/file.jpg';
        $binaryFile = new BinaryFile(['id' => $id]);

        $this->draftIoServiceMock
            ->expects(self::once())
            ->method('loadBinaryFileByUri')
            ->with($internalId)
            ->willReturn($binaryFile);

        $this->publishedIoServiceMock->expects(self::never())->method('loadBinaryFile');

        self::assertSame(
            $binaryFile,
            $this->service->loadBinaryFile($internalId)
        );
    }

    /**
     * Load from an internal published binary file path.
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\Exception
     */
    public function testLoadBinaryFilePublishedInternalPath(): void
    {
        $internalId = 'var/test/storage/images/path/file.jpg';
        $id = 'path/file.jpg';
        $binaryFile = new BinaryFile(['id' => $id]);

        $this->publishedIoServiceMock
            ->expects(self::once())
            ->method('loadBinaryFileByUri')
            ->with($internalId)
            ->willReturn($binaryFile);

        $this->draftIoServiceMock->expects(self::never())->method('loadBinaryFile');

        self::assertSame(
            $binaryFile,
            $this->service->loadBinaryFile($internalId)
        );
    }

    /**
     * Load from an external draft binary file path.
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\Exception
     */
    public function testLoadBinaryFileDraftExternalPath(): void
    {
        $id = 'path/file.jpg';
        $binaryFile = new BinaryFile(['id' => $id]);

        $this->publishedIoServiceMock
            ->expects(self::once())
            ->method('loadBinaryFile')
            ->with($id)
            ->willThrowException(new InvalidArgumentException('binaryFileId', "Can't find file with id $id"));

        $this->draftIoServiceMock
            ->expects(self::once())
            ->method('loadBinaryFile')
            ->with($id)
            ->willReturn($binaryFile);

        self::assertSame(
            $binaryFile,
            $this->service->loadBinaryFile($id)
        );
    }

    /**
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\Exception
     */
    public function testLoadBinaryFileByUriWithPublishedFile(): void
    {
        $binaryFileUri = 'var/test/images/an/image.png';
        $binaryFile = new BinaryFile(['id' => 'an/image.png']);
        $this->publishedIoServiceMock
            ->expects(self::once())
            ->method('loadBinaryFileByUri')
            ->with($binaryFileUri)
            ->willReturn($binaryFile);

        self::assertSame(
            $binaryFile,
            $this->service->loadBinaryFileByUri($binaryFileUri)
        );
    }

    /**
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\Exception
     */
    public function testLoadBinaryFileByUriWithDraftFile(): void
    {
        $binaryFileUri = 'var/test/images-versioned/an/image.png';
        $binaryFile = new BinaryFile(['id' => 'an/image.png']);

        $this->publishedIoServiceMock
            ->expects(self::once())
            ->method('loadBinaryFileByUri')
            ->with($binaryFileUri)
            ->will(self::throwException(new InvalidArgumentException('$id', "Prefix not found in {$binaryFile->id}")));

        $this->draftIoServiceMock
            ->expects(self::once())
            ->method('loadBinaryFileByUri')
            ->with($binaryFileUri)
            ->willReturn($binaryFile);

        self::assertSame(
            $binaryFile,
            $this->service->loadBinaryFileByUri($binaryFileUri)
        );
    }

    /**
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\Exception
     */
    public function testGetFileContents(): void
    {
        $contents = 'some contents';
        $path = 'path/file.png';
        $binaryFile = new BinaryFile(['id' => $path]);

        $this->draftIoServiceMock
            ->expects(self::once())
            ->method('exists')
            ->with($path)
            ->willReturn(false);

        $this->publishedIoServiceMock
            ->expects(self::once())
            ->method('getFileContents')
            ->with($binaryFile)
            ->willReturn($contents);

        $this->draftIoServiceMock->expects(self::never())->method('getFileContents');

        self::assertSame(
            $contents,
            $this->service->getFileContents($binaryFile)
        );
    }

    /**
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\Exception
     */
    public function testGetFileContentsOfDraft(): void
    {
        $contents = 'some contents';
        $path = 'path/file.png';
        $binaryFile = new BinaryFile(['id' => $path]);

        $this->draftIoServiceMock
            ->expects(self::once())
            ->method('exists')
            ->with($path)
            ->willReturn(true);

        $this->draftIoServiceMock
            ->expects(self::once())
            ->method('getFileContents')
            ->with($binaryFile)
            ->willReturn($contents);

        $this->publishedIoServiceMock->expects(self::never())->method('getFileContents');

        self::assertSame(
            $contents,
            $this->service->getFileContents($binaryFile)
        );
    }

    /**
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\Exception
     */
    public function testGetMimeType(): void
    {
        $path = 'path/file.png';
        $mimeType = 'image/png';

        $this->draftIoServiceMock
            ->expects(self::once())
            ->method('exists')
            ->with($path)
            ->willReturn(false);

        $this->publishedIoServiceMock
            ->expects(self::once())
            ->method('getMimeType')
            ->with($path)
            ->willReturn($mimeType);

        $this->draftIoServiceMock->expects(self::never())->method('getMimeType');

        self::assertSame(
            $mimeType,
            $this->service->getMimeType($path)
        );
    }

    /**
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\Exception
     */
    public function testGetMimeTypeOfDraft(): void
    {
        $path = 'path/file.png';
        $mimeType = 'image/png';

        $this->draftIoServiceMock
            ->expects(self::once())
            ->method('exists')
            ->with($path)
            ->willReturn(true);

        $this->draftIoServiceMock
            ->expects(self::once())
            ->method('getMimeType')
            ->with($path)
            ->willReturn($mimeType);

        $this->publishedIoServiceMock->expects(self::never())->method('getMimeType');

        self::assertSame(
            $mimeType,
            $this->service->getMimeType($path)
        );
    }

    /**
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\Exception
     */
    public function testCreateBinaryFile(): void
    {
        $createStruct = new BinaryFileCreateStruct();
        $binaryFile = new BinaryFile();

        $this->publishedIoServiceMock
            ->expects(self::once())
            ->method('createBinaryFile')
            ->with($createStruct)
            ->willReturn($binaryFile);

        $this->draftIoServiceMock->expects(self::never())->method('createBinaryFile');

        self::assertSame(
            $binaryFile,
            $this->service->createBinaryFile($createStruct)
        );
    }

    public function testGetUri(): void
    {
        $binaryFile = new BinaryFile(['id' => 'foo']);
        $this->publishedIoServiceMock
            ->expects(self::once())
            ->method('getUri')
            ->with($binaryFile->getId())
            ->willReturn('protocol://uri');

        $this->draftIoServiceMock->expects(self::never())->method('getUri');

        self::assertEquals(
            'protocol://uri',
            $this->service->getUri($binaryFile->getId())
        );
    }

    /**
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\Exception
     */
    public function testGetFileInputStream(): void
    {
        $binaryFile = new BinaryFile();
        $this->publishedIoServiceMock
            ->expects(self::once())
            ->method('getFileInputStream')
            ->with($binaryFile)
            ->willReturn('resource');

        $this->draftIoServiceMock->expects(self::never())->method('getFileInputStream');

        self::assertEquals(
            'resource',
            $this->service->getFileInputStream($binaryFile)
        );
    }

    /**
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\Exception
     */
    public function testDeleteBinaryFile(): void
    {
        $binaryFile = new BinaryFile();
        $this->publishedIoServiceMock
            ->expects(self::once())
            ->method('deleteBinaryFile')
            ->with($binaryFile);

        $this->draftIoServiceMock->expects(self::never())->method('deleteBinaryFile');

        $this->service->deleteBinaryFile($binaryFile);
    }

    /**
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\Exception
     */
    public function testNewBinaryCreateStructFromUploadedFile(): void
    {
        $struct = new BinaryFileCreateStruct();
        $this->publishedIoServiceMock
            ->expects(self::once())
            ->method('newBinaryCreateStructFromUploadedFile')
            ->with([])
            ->willReturn($struct);

        $this->draftIoServiceMock->expects(self::never())->method('newBinaryCreateStructFromUploadedFile');

        self::assertEquals(
            $struct,
            $this->service->newBinaryCreateStructFromUploadedFile([])
        );
    }

    protected function createConfigResolverMock(): ConfigResolverInterface & MockObject
    {
        $mock = $this->createMock(ConfigResolverInterface::class);
        $mock
            ->method('getParameter')
            ->willReturnMap([
                ['var_dir', null, null, 'var/test'],
                ['storage_dir', null, null, 'storage'],
                ['image.versioned_images_dir', null, null, 'images-versioned'],
                ['image.published_images_dir', null, null, 'images'],
            ])
        ;

        $mock
            ->method('hasParameter')
            ->willReturnMap([
                ['var_dir', null, null, true],
                ['storage_dir', null, null, true],
                ['image.versioned_images_dir', null, null, true],
                ['image.published_images_dir', null, null, true],
            ])
        ;

        return $mock;
    }
}
