<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Core\Search\Legacy\Content\WordIndexer\Gateway;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Exception\TableNotFoundException;
use Error;
use Ibexa\Contracts\Core\Persistence\Content\Type\Handler as ContentTypeHandler;
use Ibexa\Core\Persistence\Legacy\Content\Language\MaskGenerator;
use Ibexa\Core\Persistence\TransformationProcessor;
use Ibexa\Core\Search\Legacy\Content\FullTextData;
use Ibexa\Core\Search\Legacy\Content\FullTextValue;
use Ibexa\Core\Search\Legacy\Content\WordIndexer\Gateway\DoctrineDatabase;
use Ibexa\Core\Search\Legacy\Content\WordIndexer\Repository\SearchIndex;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Ibexa\Core\Search\Legacy\Content\WordIndexer\Gateway\DoctrineDatabase
 * @covers \Ibexa\Core\Search\Legacy\Content\WordIndexer\Repository\SearchIndex
 */
final class DoctrineDatabaseTest extends TestCase
{
    private Connection $connection;

    protected function setUp(): void
    {
        $this->connection = DriverManager::getConnection(['url' => 'sqlite:///:memory:']);
    }

    /**
     * @return iterable<string, array{string, callable(DoctrineDatabase): void}>
     */
    public static function providerForTestFailureRollsBackTransaction(): iterable
    {
        yield 'remove: reading content words fails' => [
            'getContentObjectWords',
            static function (DoctrineDatabase $gateway): void {
                $gateway->remove(1);
            },
        ];

        yield 'index: removing the previous entry fails' => [
            'getContentObjectWords',
            static function (DoctrineDatabase $gateway): void {
                $gateway->index(self::createFullTextData());
            },
        ];

        yield 'index: building word ids fails' => [
            'getWords',
            static function (DoctrineDatabase $gateway): void {
                $gateway->index(self::createFullTextData());
            },
        ];

        yield 'index: linking words fails' => [
            'addObjectWordLink',
            static function (DoctrineDatabase $gateway): void {
                $gateway->index(self::createFullTextData());
            },
        ];
    }

    /**
     * @dataProvider providerForTestFailureRollsBackTransaction
     *
     * @param callable(DoctrineDatabase): void $call
     */
    public function testFailureRollsBackTransaction(
        string $failingMethod,
        callable $call
    ): void {
        $error = new Error('Search index failed');

        $searchIndex = $this->createStub(SearchIndex::class);
        // configured first, so it wins over the defaults below
        $searchIndex->method($failingMethod)->willThrowException($error);
        $searchIndex->method('getContentObjectWords')->willReturn([]);
        $searchIndex->method('getWords')->willReturn([['id' => 1, 'word' => 'foo']]);

        try {
            $call($this->createGateway($searchIndex));
            self::fail('Expected the search index failure to be rethrown');
        } catch (Error $e) {
            self::assertSame($error, $e);
        }

        self::assertFalse($this->connection->isTransactionActive());
    }

    public function testPurgeFailureRollsBackTransaction(): void
    {
        // no ezsearch_* tables in this database, so the first DELETE fails
        $searchIndex = new SearchIndex($this->connection);

        try {
            $searchIndex->purge();
            self::fail('Expected purge() to fail on missing tables');
        } catch (TableNotFoundException $e) {
        }

        self::assertFalse($this->connection->isTransactionActive());
    }

    private function createGateway(SearchIndex $searchIndex): DoctrineDatabase
    {
        $transformationProcessor = $this->createStub(TransformationProcessor::class);
        $transformationProcessor->method('transform')->willReturnArgument(0);
        $transformationProcessor->method('transformByGroup')->willReturnArgument(0);

        return new DoctrineDatabase(
            $this->connection,
            $this->createStub(ContentTypeHandler::class),
            $transformationProcessor,
            $searchIndex,
            $this->createStub(MaskGenerator::class),
            ['commands' => []]
        );
    }

    private static function createFullTextData(): FullTextData
    {
        return new FullTextData([
            'id' => 1,
            'contentTypeId' => 1,
            'sectionId' => 1,
            'published' => 1,
            'values' => [
                new FullTextValue([
                    'fieldDefinitionId' => 1,
                    'fieldDefinitionIdentifier' => 'name',
                    'languageCode' => 'eng-GB',
                    'value' => 'foo',
                    'isMainAndAlwaysAvailable' => true,
                    'transformationRules' => [],
                    'splitFlag' => true,
                ]),
            ],
        ]);
    }
}
