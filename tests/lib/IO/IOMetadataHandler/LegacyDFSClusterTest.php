<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Core\IO\IOMetadataHandler;

use DateTime;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\Statement;
use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\DBAL\Result;
use Ibexa\Contracts\Core\IO\BinaryFile as SPIBinaryFile;
use Ibexa\Contracts\Core\IO\BinaryFileCreateStruct as SPIBinaryFileCreateStruct;
use Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException;
use Ibexa\Core\IO\Exception\BinaryFileNotFoundException;
use Ibexa\Core\IO\IOMetadataHandler\LegacyDFSCluster;
use Ibexa\Core\IO\UrlDecorator;
use PHPUnit\Framework\TestCase;

class LegacyDFSClusterTest extends TestCase
{
    /** @var \Ibexa\Core\IO\IOMetadataHandler&\PHPUnit\Framework\MockObject\MockObject */
    private $handler;

    /** @var \Doctrine\DBAL\Connection&\PHPUnit\Framework\MockObject\MockObject */
    private $dbalMock;

    /** @var \Doctrine\DBAL\Query\QueryBuilder&\PHPUnit\Framework\MockObject\MockObject */
    private $qbMock;

    /** @var \Ibexa\Core\IO\UrlDecorator&\PHPUnit\Framework\MockObject\MockObject */
    private $urlDecoratorMock;

    protected function setUp(): void
    {
        $this->dbalMock = $this->createMock(Connection::class);

        $this->qbMock = $this->getMockBuilder(QueryBuilder::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->dbalMock->method('createQueryBuilder')->willReturn($this->qbMock);
        $this->urlDecoratorMock = $this->createMock(UrlDecorator::class);

        $this->handler = new LegacyDFSCluster(
            $this->dbalMock,
            $this->urlDecoratorMock
        );
    }

    /**
     * @return iterable<array{string, string, int, \DateTime, \DateTime}>
     */
    public function providerCreate(): iterable
    {
        return [
            ['prefix/my/file.png', 'image/png', 123, new DateTime('@1307155200'), new DateTime('@1307155200')],
            ['prefix/my/file.png', 'image/png', 123, new DateTime('@1307155200'), new DateTime('@1307155200')], // Duplicate, should not fail
            ['prefix/my/file.png', 'image/png', 123, new DateTime('@1307155242'), new DateTime('@1307155242')],
        ];
    }

    /**
     * @dataProvider providerCreate
     */
    public function testCreate(string $id, string $mimeType, int $size, \DateTime $mtime, \DateTime $mtimeExpected): void
    {
        $this->dbalMock
            ->expects(self::once())
            ->method('insert')
            ->with(LegacyDFSCluster::DFS_FILE_TABLE);

        $spiCreateStruct = new SPIBinaryFileCreateStruct();
        $spiCreateStruct->id = $id;
        $spiCreateStruct->mimeType = $mimeType;
        $spiCreateStruct->size = $size;
        $spiCreateStruct->mtime = $mtime;

        $spiBinary = $this->handler->create($spiCreateStruct);

        self::assertInstanceOf(SPIBinaryFile::class, $spiBinary);
        self::assertEquals($mtimeExpected, $spiBinary->mtime);
    }

    public function testCreateInvalidArgument(): void
    {
        $this->dbalMock
            ->expects(self::never())
            ->method('insert');

        $spiCreateStruct = new SPIBinaryFileCreateStruct();
        $spiCreateStruct->id = 'prefix/my/file.png';
        $spiCreateStruct->mimeType = 'image/png';
        $spiCreateStruct->size = 123;
        $spiCreateStruct->mtime = 1307155242; // Invalid, should be a DateTime

        $this->expectException(InvalidArgumentException::class);
        $this->handler->create($spiCreateStruct);
    }

    public function testDelete(): void
    {
        $this->dbalMock
            ->expects(self::once())
            ->method('delete')
            ->with(LegacyDFSCluster::DFS_FILE_TABLE)
            ->willReturn(1);

        $this->handler->delete('prefix/my/file.png');
    }

    public function testDeleteNotFound(): void
    {
        $this->dbalMock
            ->expects(self::once())
            ->method('delete')
            ->with(LegacyDFSCluster::DFS_FILE_TABLE)
            ->willReturn(0);

        $this->expectException(BinaryFileNotFoundException::class);
        $this->handler->delete('prefix/my/file.png');
    }

    public function testLoad(): void
    {
        $this->setupQueryBuilderLoad(1, ['size' => 123, 'datatype' => 'image/png', 'mtime' => 1307155200]);

        $expectedSpiBinaryFile = new SPIBinaryFile();
        $expectedSpiBinaryFile->id = 'prefix/my/file.png';
        $expectedSpiBinaryFile->size = 123;
        $expectedSpiBinaryFile->mtime = new DateTime('@1307155200');

        self::assertEquals(
            $expectedSpiBinaryFile,
            $this->handler->load('prefix/my/file.png')
        );
    }

    public function testLoadNotFound(): void
    {
        $this->setupQueryBuilderLoad(0, null);

        $this->expectException(BinaryFileNotFoundException::class);
        $this->handler->load('prefix/my/file.png');
    }

    public function testExists(): void
    {
        $this->setupQueryBuilderLoad(1, null);

        self::assertTrue($this->handler->exists('prefix/my/file.png'));
    }

    public function testExistsNot(): void
    {
        $this->setupQueryBuilderLoad(0, null);

        self::assertFalse($this->handler->exists('prefix/my/file.png'));
    }

    public function testDeletedirectory(): void
    {
        $this->urlDecoratorMock
            ->expects(self::once())
            ->method('decorate')
            ->willReturn('prefix/images/_alias/subfolder');

        $this->qbMock
            ->expects(self::once())
            ->method('delete')
            ->with(LegacyDFSCluster::DFS_FILE_TABLE)
            ->willReturnSelf();

        $this->qbMock
            ->expects(self::once())
            ->method('where')
            ->with('name LIKE :spiPath ESCAPE :esc')
            ->willReturnSelf();

        $this->qbMock
            ->expects(self::exactly(2))
            ->method('setParameter')
            ->withConsecutive(
                ['esc', '\\'],
                ['spiPath', 'prefix/images/\_alias/subfolder/%'],
            )
            ->willReturnSelf();

        $this->qbMock
            ->expects(self::once())
            ->method('executeStatement')
            ->willReturn(1);

        $this->handler->deleteDirectory('images/_alias/subfolder/');
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject
     */
    protected function createDbalStatementMock()
    {
        return $this->createMock(Statement::class);
    }

    /**
     * @param array<mixed>|null $result
     */
    private function setupQueryBuilderLoad(int $rowCount, ?array $result): void
    {
        $resultMock = $this->createMock(Result::class);
        $resultMock
            ->expects(self::once())
            ->method('rowCount')
            ->willReturn($rowCount);

        if ($result === null) {
            $resultMock
                ->expects(self::never())
                ->method('fetchAssociative');
        } else {
            $resultMock
                ->expects(self::once())
                ->method('fetchAssociative')
                ->willReturn($result);
        }

        $this->qbMock
            ->expects(self::once())
            ->method('select')
            ->willReturnSelf();

        $this->qbMock
            ->expects(self::once())
            ->method('from')
            ->willReturnSelf();

        $this->qbMock
            ->method('andWhere')
            ->willReturnSelf();

        $this->qbMock
            ->method('setParameter')
            ->willReturnSelf();

        $this->qbMock
            ->method('executeQuery')
            ->willReturn($resultMock);
    }
}
