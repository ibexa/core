<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Core\IO;

use Ibexa\Core\IO\TolerantIOService;
use Ibexa\Core\IO\Values\BinaryFile;
use Ibexa\Core\IO\Values\MissingBinaryFile;
use Override;

/**
 * @covers \Ibexa\Core\IO\IOService
 */
class TolerantIOServiceTest extends IOServiceTest
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->ioService = new TolerantIOService(
            $this->metadataHandlerMock,
            $this->binarydataHandlerMock,
            $this->mimeTypeDetectorMock,
            ['prefix' => self::PREFIX]
        );
    }

    public function testLoadBinaryFileNotFound(): BinaryFile
    {
        $prefixedUri = $this->mockGettingPrefixedUriFromDataHandler('id.ext');

        $binaryFile = $this->loadBinaryFileNotFound();

        self::assertEquals(
            new MissingBinaryFile(['id' => 'id.ext', 'uri' => $prefixedUri]),
            $binaryFile
        );

        return $binaryFile;
    }

    /**
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException
     */
    public function testCreateMissingBinaryFile(): void
    {
        $id = 'id.ext';
        $prefixedUri = $this->mockGettingPrefixedUriFromDataHandler($id);

        $binaryFile = $this->loadBinaryFileNotFound();
        self::assertEquals(
            new MissingBinaryFile(['id' => 'id.ext', 'uri' => $prefixedUri]),
            $binaryFile
        );
    }

    /**
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException
     */
    #[Override]
    public function testDeleteBinaryFileNotFound(): void
    {
        $this->expectNotToPerformAssertions();
        $this->deleteBinaryFileNotFound();
    }

    public function testLoadBinaryFileByUriNotFound(): BinaryFile
    {
        $prefixedUri = $this->mockGettingPrefixedUriFromDataHandler(self::BINARY_FILE_ID_MY_PATH);

        $binaryFile = $this->loadBinaryFileByUriNotFound();
        self::assertEquals(
            new MissingBinaryFile(['id' => 'my/path.png', 'uri' => $prefixedUri]),
            $binaryFile
        );

        return $binaryFile;
    }
}
