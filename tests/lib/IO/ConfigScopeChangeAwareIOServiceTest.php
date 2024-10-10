<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Core\IO;

use Ibexa\Contracts\Core\SiteAccess\ConfigResolverInterface;
use Ibexa\Core\IO\ConfigScopeChangeAwareIOService;
use Ibexa\Core\IO\IOServiceInterface;
use Ibexa\Core\IO\Values\BinaryFile;
use Ibexa\Core\IO\Values\BinaryFileCreateStruct;
use Ibexa\Core\MVC\Symfony\Event\ScopeChangeEvent;
use PHPUnit\Framework\TestCase;

final class ConfigScopeChangeAwareIOServiceTest extends TestCase
{
    protected const PREFIX = 'test-prefix';
    protected const PREFIX_PARAMETER_NAME = 'param';

    /** @var \Ibexa\Core\IO\ConfigScopeChangeAwareIOService */
    protected $ioService;

    /** @var \Ibexa\Core\IO\ConfigScopeChangeAwareIOService|\PHPUnit\Framework\MockObject\MockObject */
    protected $innerIOService;

    /** @var \Ibexa\Contracts\Core\SiteAccess\ConfigResolverInterface|\PHPUnit\Framework\MockObject\MockObject */
    protected $configResolver;

    protected function setUp(): void
    {
        parent::setUp();

        $this->configResolver = $this->createMock(ConfigResolverInterface::class);
        $this->configResolver
            ->method('getParameter')
            ->with(self::PREFIX_PARAMETER_NAME, null, null)
            ->willReturn(self::PREFIX)
        ;

        $this->innerIOService = $this->createMock(IOServiceInterface::class);
        $this->ioService = new ConfigScopeChangeAwareIOService(
            $this->configResolver,
            $this->innerIOService,
            self::PREFIX_PARAMETER_NAME
        );
    }

    public function testConstructor(): void
    {
        $this->innerIOService
            ->expects(self::once())
            ->method('setPrefix')
            ->with(self::PREFIX)
        ;

        new ConfigScopeChangeAwareIOService(
            $this->configResolver,
            $this->innerIOService,
            self::PREFIX_PARAMETER_NAME
        );
    }

    public function testSetPrefix(): void
    {
        $this->innerIOService
            ->expects(self::once())
            ->method('setPrefix')
        ;

        $this->ioService->setPrefix(self::PREFIX);
    }

    public function testNewBinaryCreateStructFromLocalFile(): void
    {
        $expectedBinaryFileCreateStruct = new BinaryFileCreateStruct();
        $localFile = '/path/to/local/file.txt';

        $this->innerIOService
            ->expects(self::once())
            ->method('newBinaryCreateStructFromLocalFile')
            ->with($localFile)
            ->willReturn($expectedBinaryFileCreateStruct)
        ;

        $binaryFileCreateStruct = $this->innerIOService->newBinaryCreateStructFromLocalFile($localFile);

        self::assertEquals($expectedBinaryFileCreateStruct, $binaryFileCreateStruct);
    }

    public function testExists(): void
    {
        $binaryFileId = 'test-id';

        $this->innerIOService
            ->expects(self::once())
            ->method('exists')
            ->with($binaryFileId)
            ->willReturn(true)
        ;

        self::assertTrue($this->innerIOService->exists($binaryFileId));
    }

    public function testLoadBinaryFile(): void
    {
        $expectedBinaryFile = new BinaryFile();
        $binaryFileId = 'test-id';

        $this->innerIOService
            ->expects(self::once())
            ->method('loadBinaryFile')
            ->with($binaryFileId)
            ->willReturn($expectedBinaryFile)
        ;

        $binaryFile = $this->innerIOService->loadBinaryFile($binaryFileId);

        self::assertEquals($expectedBinaryFile, $binaryFile);
    }

    public function testLoadBinaryFileByUri(): void
    {
        $expectedBinaryFile = new BinaryFile();
        $uri = 'http://example.com/file.pdf';

        $this->innerIOService
            ->expects(self::once())
            ->method('loadBinaryFileByUri')
            ->with($uri)
            ->willReturn($expectedBinaryFile)
        ;

        $binaryFile = $this->innerIOService->loadBinaryFileByUri($uri);

        self::assertEquals($expectedBinaryFile, $binaryFile);
    }

    public function testGetFileContents(): void
    {
        $binaryFile = new BinaryFile();
        $expectedContents = 'test';

        $this->innerIOService
            ->expects(self::once())
            ->method('getFileContents')
            ->with($binaryFile)
            ->willReturn($expectedContents)
        ;

        $contents = $this->innerIOService->getFileContents($binaryFile);

        self::assertEquals($expectedContents, $contents);
    }

    public function testCreateBinaryFile(): void
    {
        $expectedBinaryFile = new BinaryFile();
        $binaryFileCreateStruct = new BinaryFileCreateStruct();

        $this->innerIOService
            ->expects(self::once())
            ->method('createBinaryFile')
            ->with($binaryFileCreateStruct)
            ->willReturn($expectedBinaryFile)
        ;

        $binaryFile = $this->innerIOService->createBinaryFile($binaryFileCreateStruct);

        self::assertEquals($expectedBinaryFile, $binaryFile);
    }

    public function testGetUri(): void
    {
        $expectedUri = 'http://example.com/test.pdf';
        $binaryFileId = 'file-id';

        $this->innerIOService
            ->expects(self::once())
            ->method('getUri')
            ->with($binaryFileId)
            ->willReturn($expectedUri)
        ;

        $uri = $this->innerIOService->getUri($binaryFileId);

        self::assertEquals($expectedUri, $uri);
    }

    public function testGetMimeType(): void
    {
        $expectedMimeType = 'text/xml';
        $binaryFileId = 'file-id';

        $this->innerIOService
            ->expects(self::once())
            ->method('getMimeType')
            ->with($binaryFileId)
            ->willReturn($expectedMimeType)
        ;

        $mimeType = $this->innerIOService->getMimeType($binaryFileId);

        self::assertEquals($expectedMimeType, $mimeType);
    }

    public function testGetFileInputStream(): void
    {
        $expectedFileInputStream = 'resource';
        $binaryFile = new BinaryFile();

        $this->innerIOService
            ->expects(self::once())
            ->method('getFileInputStream')
            ->with($binaryFile)
            ->willReturn($expectedFileInputStream)
        ;

        $fileInputStream = $this->innerIOService->getFileInputStream($binaryFile);

        self::assertEquals($expectedFileInputStream, $fileInputStream);
    }

    public function testDeleteBinaryFile(): void
    {
        $binaryFile = new BinaryFile();

        $this->innerIOService
            ->expects(self::once())
            ->method('deleteBinaryFile')
            ->with($binaryFile)
        ;

        $this->innerIOService->deleteBinaryFile($binaryFile);
    }

    public function testNewBinaryCreateStructFromUploadedFile(): void
    {
        $expectedBinaryFileCreateStruct = new BinaryFileCreateStruct();
        $uploadedFile = [
            'name' => 'example.jpg',
            'type' => 'image/jpeg',
            'tmp_name' => '/tmp/phpn3FmFr',
            'error' => 0,
            'size' => 15476,
        ];

        $this->innerIOService
            ->expects(self::once())
            ->method('newBinaryCreateStructFromUploadedFile')
            ->with($uploadedFile)
            ->willReturn($expectedBinaryFileCreateStruct)
        ;

        $binaryFileCreateStruct = $this->innerIOService->newBinaryCreateStructFromUploadedFile($uploadedFile);

        self::assertEquals($expectedBinaryFileCreateStruct, $binaryFileCreateStruct);
    }

    public function testDeleteDirectory(): void
    {
        $path = '/path/to/directory';

        $this->innerIOService
            ->expects(self::once())
            ->method('deleteDirectory')
            ->with($path)
        ;

        $this->innerIOService->deleteDirectory($path);
    }

    public function testOnConfigScopeChange(): void
    {
        $event = $this->createMock(ScopeChangeEvent::class);
        $this->innerIOService
            ->expects(self::once())
            ->method('setPrefix')
            ->with(self::PREFIX);

        $this->ioService->onConfigScopeChange($event);
    }
}
