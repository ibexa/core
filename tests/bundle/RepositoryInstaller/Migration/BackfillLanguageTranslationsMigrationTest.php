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

    public function testChunksUrlAliasesById(): void
    {
        $connection = $this->getDatabaseConnection();
        foreach ([1 => ['foo', 'acbd18db4cc2f85cedef654fccc4a4d8'], 5001 => ['bar', '37b51d194a7513e45b56f6524f2d51f2']] as $id => [$text, $textMd5]) {
            $connection->insert('ibexa_url_alias_ml', [
                'parent' => 0,
                'text_md5' => $textMd5,
                'id' => $id,
                'text' => $text,
                'action' => 'eznode:' . $id,
                'action_type' => 'eznode',
                'lang_mask' => 7,
            ], ['lang_mask' => ParameterType::INTEGER]);
        }

        $queries = $this->runMigration();

        self::assertCount(
            2,
            array_filter(
                $queries,
                static fn (Query $query): bool => str_contains($query->getStatement(), 'ibexa_url_alias_ml_translation')
            )
        );
        self::assertEquals(
            [[1, 2], [1, 4], [5001, 2], [5001, 4]],
            $connection->fetchAllNumeric(
                'SELECT u.id, t.language_id FROM ibexa_url_alias_ml_translation t
                 JOIN ibexa_url_alias_ml u ON u.parent = t.parent AND u.text_md5 = t.text_md5
                 ORDER BY u.id, t.language_id'
            )
        );
    }

    /**
     * @return Query[]
     */
    private function runMigration(): array
    {
        $connection = $this->getDatabaseConnection();
        $migration = new BackfillLanguageTranslationsMigration($connection, new NullLogger(), new MigrationRunContext());
        $migration->up(new Schema());

        $queries = $migration->getSql();
        foreach ($queries as $query) {
            $connection->executeStatement($query->getStatement(), $query->getParameters(), $query->getTypes());
        }

        return $queries;
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
