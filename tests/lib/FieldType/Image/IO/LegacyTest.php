<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Core\FieldType\Image\IO;

use Ibexa\Contracts\Core\SiteAccess\ConfigResolverInterface;
use Ibexa\Core\Base\Exceptions\InvalidArgumentException;
use Ibexa\Core\FieldType\Image\IO\Legacy as LegacyIOService;
use Ibexa\Core\FieldType\Image\IO\OptionsProvider;
use Ibexa\Core\IO\IOServiceInterface;
use Ibexa\Core\IO\Values\BinaryFile;
use Ibexa\Core\IO\Values\BinaryFileCreateStruct;
use PHPUnit\Framework\TestCase;

class LegacyTest extends TestCase
{
    /** @var \Ibexa\Core\FieldType\Image\IO\Legacy */
    protected $service;

    /**
     * Internal IOService instance for published images.
     *
     * @var \Ibexa\Core\IO\IOServiceInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $publishedIoServiceMock;

    /**
     * Internal IOService instance for draft images.
     *
     * @var \Ibexa\Core\IO\IOServiceInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $draftIoServiceMock;

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

    public function testNewBinaryCreateStructFromLocalFile()
    {
        $path = '/tmp/file.png';
        $struct = new BinaryFileCreateStruct();
        $this->publishedIoServiceMock
            ->expects(self::once())
            ->method('newBinaryCreateStructFromLocalFile')
            ->with($path)
            ->will(self::returnValue($struct));

        $this->draftIoServiceMock->expects(self::never())->method('newBinaryCreateStructFromLocalFile');

        self::assertEquals(
            $struct,
            $this->service->newBinaryCreateStructFromLocalFile($path)
        );
    }

    public function testExists()
    {
        $path = 'path/file.png';
        $this->publishedIoServiceMock
            ->expects(self::once())
            ->method('exists')
            ->with($path)
            ->will(self::returnValue(true));

        $this->draftIoServiceMock->expects(self::never())->method('exists');

        self::assertTrue(
            $this->service->exists($path)
        );
    }

    /**
     * Standard binary file, with regular id.
     */
    public function testLoadBinaryFile()
    {
        $id = 'path/file.jpg';
        $binaryFile = new BinaryFile(['id' => $id]);

        $this->publishedIoServiceMock
            ->expects(self::once())
            ->method('loadBinaryFile')
            ->with($id)
            ->will(self::returnValue($binaryFile));

        $this->draftIoServiceMock->expects(self::never())->method('loadBinaryFile');

        self::assertSame(
            $binaryFile,
            $this->service->loadBinaryFile($id)
        );
    }

    /**
     * Load from internal draft binary file path.
     */
    public function testLoadBinaryFileDraftInternalPath()
    {
        $internalId = 'var/test/storage/images-versioned/path/file.jpg';
        $id = 'path/file.jpg';
        $binaryFile = new BinaryFile(['id' => $id]);

        $this->draftIoServiceMock
            ->expects(self::once())
            ->method('loadBinaryFileByUri')
            ->with($internalId)
            ->will(self::returnValue($binaryFile));

        $this->publishedIoServiceMock->expects(self::never())->method('loadBinaryFile');

        self::assertSame(
            $binaryFile,
            $this->service->loadBinaryFile($internalId)
        );
    }

    /**
     * Load from internal published binary file path.
     */
    public function testLoadBinaryFilePublishedInternalPath()
    {
        $internalId = 'var/test/storage/images/path/file.jpg';
        $id = 'path/file.jpg';
        $binaryFile = new BinaryFile(['id' => $id]);

        $this->publishedIoServiceMock
            ->expects(self::once())
            ->method('loadBinaryFileByUri')
            ->with($internalId)
            ->will(self::returnValue($binaryFile));

        $this->draftIoServiceMock->expects(self::never())->method('loadBinaryFile');

        self::assertSame(
            $binaryFile,
            $this->service->loadBinaryFile($internalId)
        );
    }

    /**
     * Load from external draft binary file path.
     */
    public function testLoadBinaryFileDraftExternalPath()
    {
        $id = 'path/file.jpg';
        $binaryFile = new BinaryFile(['id' => $id]);

        $this->publishedIoServiceMock
            ->expects(self::once())
            ->method('loadBinaryFile')
            ->with($id)
            ->will(self::throwException(new InvalidArgumentException('binaryFileId', "Can't find file with id $id}")));

        $this->draftIoServiceMock
            ->expects(self::once())
            ->method('loadBinaryFile')
            ->with($id)
            ->will(self::returnValue($binaryFile));

        self::assertSame(
            $binaryFile,
            $this->service->loadBinaryFile($id)
        );
    }

    public function testLoadBinaryFileByUriWithPublishedFile()
    {
        $binaryFileUri = 'var/test/images/an/image.png';
        $binaryFile = new BinaryFile(['id' => 'an/image.png']);
        $this->publishedIoServiceMock
            ->expects(self::once())
            ->method('loadBinaryFileByUri')
            ->with($binaryFileUri)
            ->will(self::returnValue($binaryFile));

        self::assertSame(
            $binaryFile,
            $this->service->loadBinaryFileByUri($binaryFileUri)
        );
    }

    public function testLoadBinaryFileByUriWithDraftFile()
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
            ->will(self::returnValue($binaryFile));

        self::assertSame(
            $binaryFile,
            $this->service->loadBinaryFileByUri($binaryFileUri)
        );
    }

    public function testGetFileContents()
    {
        $contents = 'some contents';
        $path = 'path/file.png';
        $binaryFile = new BinaryFile(['id' => $path]);

        $this->draftIoServiceMock
            ->expects(self::once())
            ->method('exists')
            ->with($path)
            ->will(self::returnValue(false));

        $this->publishedIoServiceMock
            ->expects(self::once())
            ->method('getFileContents')
            ->with($binaryFile)
            ->will(self::returnValue($contents));

        $this->draftIoServiceMock->expects(self::never())->method('getFileContents');

        self::assertSame(
            $contents,
            $this->service->getFileContents($binaryFile)
        );
    }

    public function testGetFileContentsOfDraft()
    {
        $contents = 'some contents';
        $path = 'path/file.png';
        $binaryFile = new BinaryFile(['id' => $path]);

        $this->draftIoServiceMock
            ->expects(self::once())
            ->method('exists')
            ->with($path)
            ->will(self::returnValue(true));

        $this->draftIoServiceMock
            ->expects(self::once())
            ->method('getFileContents')
            ->with($binaryFile)
            ->will(self::returnValue($contents));

        $this->publishedIoServiceMock->expects(self::never())->method('getFileContents');

        self::assertSame(
            $contents,
            $this->service->getFileContents($binaryFile)
        );
    }

    public function testGetMimeType()
    {
        $path = 'path/file.png';
        $mimeType = 'image/png';

        $this->draftIoServiceMock
            ->expects(self::once())
            ->method('exists')
            ->with($path)
            ->will(self::returnValue(false));

        $this->publishedIoServiceMock
            ->expects(self::once())
            ->method('getMimeType')
            ->with($path)
            ->will(self::returnValue($mimeType));

        $this->draftIoServiceMock->expects(self::never())->method('getMimeType');

        self::assertSame(
            $mimeType,
            $this->service->getMimeType($path)
        );
    }

    public function testGetMimeTypeOfDraft()
    {
        $path = 'path/file.png';
        $mimeType = 'image/png';

        $this->draftIoServiceMock
            ->expects(self::once())
            ->method('exists')
            ->with($path)
            ->will(self::returnValue(true));

        $this->draftIoServiceMock
            ->expects(self::once())
            ->method('getMimeType')
            ->with($path)
            ->will(self::returnValue($mimeType));

        $this->publishedIoServiceMock->expects(self::never())->method('getMimeType');

        self::assertSame(
            $mimeType,
            $this->service->getMimeType($path)
        );
    }

    public function testCreateBinaryFile()
    {
        $createStruct = new BinaryFileCreateStruct();
        $binaryFile = new BinaryFile();

        $this->publishedIoServiceMock
            ->expects(self::once())
            ->method('createBinaryFile')
            ->with($createStruct)
            ->will(self::returnValue($binaryFile));

        $this->draftIoServiceMock->expects(self::never())->method('createBinaryFile');

        self::assertSame(
            $binaryFile,
            $this->service->createBinaryFile($createStruct)
        );
    }

    public function testGetUri()
    {
        $binaryFile = new BinaryFile();
        $this->publishedIoServiceMock
            ->expects(self::once())
            ->method('getUri')
            ->with($binaryFile)
            ->will(self::returnValue('protocol://uri'));

        $this->draftIoServiceMock->expects(self::never())->method('getUri');

        self::assertEquals(
            'protocol://uri',
            $this->service->getUri($binaryFile)
        );
    }

    public function testGetFileInputStream()
    {
        $binaryFile = new BinaryFile();
        $this->publishedIoServiceMock
            ->expects(self::once())
            ->method('getFileInputStream')
            ->with($binaryFile)
            ->will(self::returnValue('resource'));

        $this->draftIoServiceMock->expects(self::never())->method('getFileInputStream');

        self::assertEquals(
            'resource',
            $this->service->getFileInputStream($binaryFile)
        );
    }

    public function testDeleteBinaryFile()
    {
        $binaryFile = new BinaryFile();
        $this->publishedIoServiceMock
            ->expects(self::once())
            ->method('deleteBinaryFile')
            ->with($binaryFile);

        $this->draftIoServiceMock->expects(self::never())->method('deleteBinaryFile');

        $this->service->deleteBinaryFile($binaryFile);
    }

    public function testNewBinaryCreateStructFromUploadedFile()
    {
        $struct = new BinaryFileCreateStruct();
        $this->publishedIoServiceMock
            ->expects(self::once())
            ->method('newBinaryCreateStructFromUploadedFile')
            ->with([])
            ->will(self::returnValue($struct));

        $this->draftIoServiceMock->expects(self::never())->method('newBinaryCreateStructFromUploadedFile');

        self::assertEquals(
            $struct,
            $this->service->newBinaryCreateStructFromUploadedFile([])
        );
    }

    /**
     * @return \Ibexa\Contracts\Core\SiteAccess\ConfigResolverInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected function createConfigResolverMock(): ConfigResolverInterface
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
