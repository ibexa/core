<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Bundle\RepositoryInstaller\Migration;

use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Schema\Schema;
use Ibexa\Bundle\RepositoryInstaller\Migration\BackfillLanguageTranslationsMigration;
use Ibexa\Contracts\DoctrineMigrations\Migrations\MigrationRunContext;
use Ibexa\Tests\Core\Persistence\Legacy\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use Psr\Log\NullLogger;

#[CoversClass(BackfillLanguageTranslationsMigration::class)]
final class BackfillLanguageTranslationsMigrationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->insertDatabaseFixture(
            __DIR__ . '/../../../lib/Persistence/Legacy/Content/_fixtures/languages.php'
        );

        $connection = $this->getDatabaseConnection();
        foreach ([1 => 2, 2 => 7] as $id => $languageMask) {
            $connection->insert('ibexa_content', [
                'id' => $id,
                'content_type_id' => 1,
                'current_version' => 1,
                'initial_language_id' => 2,
                'language_mask' => $languageMask,
                'name' => 'Content ' . $id,
                'owner_id' => 14,
                'remote_id' => 'content-' . $id,
            ]);
            $connection->insert('ibexa_content_version', [
                'id' => $id,
                'contentobject_id' => $id,
                'version' => 1,
                'initial_language_id' => 2,
                'language_mask' => $languageMask,
            ]);
        }
    }

    public function testBackfillsAlwaysAvailableAndTranslations(): void
    {
        $this->runMigration();

        $this->assertBackfilled();
    }

    public function testRerunAfterAnInterruptedRunCompletesTheBackfill(): void
    {
        $this->runMigration();
        $this->getDatabaseConnection()->executeStatement(
            'UPDATE ibexa_content_version SET always_available = :alwaysAvailable',
            ['alwaysAvailable' => false],
            ['alwaysAvailable' => ParameterType::BOOLEAN]
        );

        $this->runMigration();

        $this->assertBackfilled();
    }

    private function runMigration(): void
    {
        $connection = $this->getDatabaseConnection();
        $migration = new BackfillLanguageTranslationsMigration($connection, new NullLogger(), new MigrationRunContext());
        $migration->up(new Schema());

        foreach ($migration->getSql() as $query) {
            $connection->executeStatement($query->getStatement(), $query->getParameters(), $query->getTypes());
        }
    }

    private function assertBackfilled(): void
    {
        $connection = $this->getDatabaseConnection();

        foreach (['ibexa_content', 'ibexa_content_version'] as $table) {
            self::assertEquals(
                [1 => 0, 2 => 1],
                $connection->fetchAllKeyValue("SELECT id, always_available FROM {$table} ORDER BY id"),
                $table
            );
        }

        self::assertEquals(
            [[1, 2], [2, 2], [2, 4]],
            $connection->fetchAllNumeric('SELECT content_id, language_id FROM ibexa_content_translation ORDER BY content_id, language_id')
        );
        self::assertEquals(
            [[1, 2], [2, 2], [2, 4]],
            $connection->fetchAllNumeric('SELECT content_version_id, language_id FROM ibexa_content_version_translation ORDER BY content_version_id, language_id')
        );
    }
}
