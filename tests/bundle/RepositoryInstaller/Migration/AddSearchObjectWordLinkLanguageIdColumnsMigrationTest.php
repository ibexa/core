<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Bundle\RepositoryInstaller\Migration;

use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\Query\Query;
use Ibexa\Bundle\RepositoryInstaller\Migration\AddSearchObjectWordLinkLanguageIdColumnsMigration;
use Ibexa\Contracts\DoctrineMigrations\Migrations\MigrationRunContext;
use Ibexa\Tests\Core\Persistence\Legacy\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use Psr\Log\NullLogger;

#[CoversClass(AddSearchObjectWordLinkLanguageIdColumnsMigration::class)]
final class AddSearchObjectWordLinkLanguageIdColumnsMigrationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->getDatabaseConnection()->executeStatement('PRAGMA foreign_keys = OFF');
        $this->getDatabaseConnection()->executeStatement(
            'ALTER TABLE ibexa_search_object_word_link ADD COLUMN language_mask INTEGER DEFAULT 0 NOT NULL'
        );
        foreach ([1 => 3, 5001 => 4] as $id => $languageMask) {
            $this->getDatabaseConnection()->insert(
                'ibexa_search_object_word_link',
                ['id' => $id, 'identifier' => '', 'language_mask' => $languageMask],
                ['language_mask' => ParameterType::INTEGER]
            );
        }
    }

    public function testBackfillsTheColumnsInChunksById(): void
    {
        self::assertCount(2, $this->runMigration());
        $this->assertBackfilled();
    }

    public function testRerunAfterAnInterruptedRunCompletesTheBackfill(): void
    {
        $this->runMigration();
        $this->getDatabaseConnection()->executeStatement('UPDATE ibexa_search_object_word_link SET language_id = 0');

        $this->runMigration();

        $this->assertBackfilled();
    }

    /**
     * @return Query[]
     */
    private function runMigration(): array
    {
        $connection = $this->getDatabaseConnection();
        $migration = new AddSearchObjectWordLinkLanguageIdColumnsMigration($connection, new NullLogger(), new MigrationRunContext());
        $migration->up(new Schema());

        $queries = $migration->getSql();
        foreach ($queries as $query) {
            $connection->executeStatement($query->getStatement(), $query->getParameters(), $query->getTypes());
        }

        return $queries;
    }

    private function assertBackfilled(): void
    {
        self::assertEquals(
            [[1, 2, 1], [5001, 4, 0]],
            $this->getDatabaseConnection()->fetchAllNumeric(
                'SELECT id, language_id, is_main_and_always_available FROM ibexa_search_object_word_link ORDER BY id'
            )
        );
    }
}
