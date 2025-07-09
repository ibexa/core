<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Bundle\IO\Migration;

use DateTime;
use Ibexa\Bundle\IO\ApiLoader\HandlerRegistry;
use Ibexa\Bundle\IO\Migration\FileMigrator\FileMigrator;
use Ibexa\Contracts\Core\IO\BinaryFile;
use Ibexa\Core\IO\IOBinarydataHandler;
use Ibexa\Core\IO\IOMetadataHandler;
use Ibexa\Core\IO\IOMetadataHandler\Flysystem;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Ibexa\Bundle\IO\Migration\MigrationHandler
 */
final class FileMigratorTest extends TestCase
{
    /** @var \Ibexa\Bundle\IO\ApiLoader\HandlerRegistry<\Ibexa\Core\IO\IOMetadataHandler>&\PHPUnit\Framework\MockObject\MockObject */
    private HandlerRegistry & MockObject $metadataHandlerRegistry;

    /** @var \Ibexa\Bundle\IO\ApiLoader\HandlerRegistry<\Ibexa\Core\IO\IOBinarydataHandler>&\PHPUnit\Framework\MockObject\MockObject */
    private HandlerRegistry & MockObject $binaryHandlerRegistry;

    private FileMigrator $fileMigrator;

    private Flysystem & MockObject $metadataFlysystem;

    private IOMetadataHandler\LegacyDFSCluster&MockObject $metadataLegacyDFSCluster;

    private IOBinarydataHandler & MockObject $binaryFlysystemFrom;

    private IOBinarydataHandler & MockObject $binaryFlysystemTo;

    protected function setUp(): void
    {
        parent::setUp();

        $this->metadataHandlerRegistry = $this->createMock(HandlerRegistry::class);
        $this->binaryHandlerRegistry = $this->createMock(HandlerRegistry::class);

        $this->metadataFlysystem = $this->createMock(Flysystem::class);
        $this->metadataLegacyDFSCluster = $this->createMock(IOMetadataHandler\LegacyDFSCluster::class);

        $this->binaryFlysystemFrom = $this->createMock(IOBinarydataHandler::class);
        $this->binaryFlysystemTo = $this->createMock(IOBinarydataHandler::class);

        $this->fileMigrator = new FileMigrator($this->metadataHandlerRegistry, $this->binaryHandlerRegistry);
    }

    public function testMigrateFile(): void
    {
        $this->metadataHandlerRegistry
            ->expects(self::exactly(2))
            ->method('getConfiguredHandler')
            ->willReturnMap(
                [
                    ['default', $this->metadataFlysystem],
                    ['dfs', $this->metadataLegacyDFSCluster],
                ]
            );

        $this->binaryHandlerRegistry
            ->expects(self::exactly(2))
            ->method('getConfiguredHandler')
            ->willReturnMap(
                [
                    ['default', $this->binaryFlysystemFrom],
                    ['nfs', $this->binaryFlysystemTo],
                ]
            );

        $this->fileMigrator->setIODataHandlersByIdentifiers('default', 'default', 'dfs', 'nfs');

        $binaryFile = new BinaryFile();
        $binaryFile->id = '1234.jpg';
        $binaryFile->mtime = new DateTime();
        $binaryFile->size = 12345;
        $binaryFile->uri = '1/1234.jpg';

        $this->binaryFlysystemTo
            ->expects(self::once())
            ->method('create');

        $this->metadataLegacyDFSCluster
            ->expects(self::once())
            ->method('create');

        $flag = $this->fileMigrator->migrateFile($binaryFile);

        self::assertTrue($flag);
    }

    public function testSkipMigratingIfSameHandlers(): void
    {
        $this->metadataHandlerRegistry
            ->expects(self::exactly(2))
            ->method('getConfiguredHandler')
            ->with('default')
            ->willReturn($this->metadataFlysystem);

        $this->binaryHandlerRegistry
            ->expects(self::exactly(2))
            ->method('getConfiguredHandler')
            ->with('default')->willReturn($this->binaryFlysystemFrom);

        $this->fileMigrator->setIODataHandlersByIdentifiers('default', 'default', 'default', 'default');

        $binaryFile = new BinaryFile();
        $binaryFile->id = 'foo/bar.pdf';

        $this->binaryFlysystemFrom
            ->expects(self::never())
            ->method('create');

        $this->metadataFlysystem
            ->expects(self::never())
            ->method('create');

        $flag = $this->fileMigrator->migrateFile($binaryFile);

        self::assertTrue($flag);
    }
}
