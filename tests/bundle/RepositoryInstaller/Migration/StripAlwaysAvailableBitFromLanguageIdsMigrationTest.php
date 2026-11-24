<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Bundle\RepositoryInstaller\Migration;

use Doctrine\DBAL\Schema\Schema;
use Ibexa\Bundle\RepositoryInstaller\Migration\StripAlwaysAvailableBitFromLanguageIdsMigration;
use Ibexa\Contracts\DoctrineMigrations\Migrations\MigrationRunContext;
use Ibexa\Tests\Core\Persistence\Legacy\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use Psr\Log\NullLogger;

#[CoversClass(StripAlwaysAvailableBitFromLanguageIdsMigration::class)]
final class StripAlwaysAvailableBitFromLanguageIdsMigrationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->insertDatabaseFixture(
            __DIR__ . '/../../../lib/Persistence/Legacy/Content/_fixtures/languages.php'
        );

        $connection = $this->getDatabaseConnection();
        $connection->executeStatement('PRAGMA foreign_keys = OFF');
        $connection->insert('ibexa_content_language', ['id' => 5, 'locale' => 'fre-FR', 'name' => 'French', 'disabled' => 0]);

        foreach ([[1, 'eng-GB', 5], [2, 'fre-FR', 5], [3, 'eng-US', 3], [4, 'eng-US', 2]] as [$id, $languageCode, $languageId]) {
            $connection->insert('ibexa_content_field', [
                'id' => $id,
                'version' => 1,
                'contentobject_id' => 1,
                'language_code' => $languageCode,
                'language_id' => $languageId,
            ]);
        }

        foreach ([['eng-GB', 5], ['fre-FR', 5]] as [$languageCode, $languageId]) {
            $connection->insert('ibexa_content_name', [
                'contentobject_id' => 1,
                'content_version' => 1,
                'content_translation' => $languageCode,
                'language_id' => $languageId,
            ]);
        }

        foreach ([[1, 'eng-GB', 5], [2, 'fre-FR', 5], [3, 'eng-US', 3]] as [$typeId, $languageLocale, $languageId]) {
            $connection->insert('ibexa_content_type_name', [
                'content_type_id' => $typeId,
                'content_type_status' => 0,
                'language_locale' => $languageLocale,
                'language_id' => $languageId,
            ]);
        }

        foreach ([[4, 5], [5, 5]] as [$realLanguageId, $languageId]) {
            $connection->insert('ibexa_object_state_group_language', [
                'contentobject_state_group_id' => 1,
                'real_language_id' => $realLanguageId,
                'language_id' => $languageId,
                'description' => '',
            ]);
        }

        foreach ([1 => 4, 2 => 2, 3 => 2] as $stateId => $defaultLanguageId) {
            $connection->insert('ibexa_object_state', [
                'id' => $stateId,
                'group_id' => 1,
                'default_language_id' => $defaultLanguageId,
                'identifier' => 'state_' . $stateId,
            ]);
        }

        foreach ([[1, 5, 'default'], [2, 3, 'default'], [2, 4, 'eng-GB'], [2, 5, 'fre-FR'], [3, 2, 'without bit'], [3, 3, 'with bit']] as [$stateId, $languageId, $name]) {
            $connection->insert('ibexa_object_state_language', [
                'contentobject_state_id' => $stateId,
                'language_id' => $languageId,
                'name' => $name,
                'description' => '',
            ]);
        }
    }

    public function testStripsTheBitAndKeepsTheRowsOfASequentialLanguage(): void
    {
        $this->runMigration();

        $this->assertStripped();
    }

    public function testRerunChangesNothing(): void
    {
        $this->runMigration();
        $this->runMigration();

        $this->assertStripped();
    }

    private function runMigration(): void
    {
        $connection = $this->getDatabaseConnection();
        $migration = new StripAlwaysAvailableBitFromLanguageIdsMigration($connection, new NullLogger(), new MigrationRunContext());
        $migration->up(new Schema());

        foreach ($migration->getSql() as $query) {
            $connection->executeStatement($query->getStatement(), $query->getParameters(), $query->getTypes());
        }
    }

    private function assertStripped(): void
    {
        $connection = $this->getDatabaseConnection();

        self::assertEquals(
            [[1, 4], [2, 5], [3, 2], [4, 2]],
            $connection->fetchAllNumeric('SELECT id, language_id FROM ibexa_content_field ORDER BY id')
        );
        self::assertEquals(
            [['eng-GB', 4], ['fre-FR', 5]],
            $connection->fetchAllNumeric('SELECT content_translation, language_id FROM ibexa_content_name ORDER BY content_translation')
        );
        self::assertEquals(
            [[1, 4], [2, 5], [3, 2]],
            $connection->fetchAllNumeric('SELECT content_type_id, language_id FROM ibexa_content_type_name ORDER BY content_type_id')
        );
        self::assertEquals(
            [[4, 4], [5, 5]],
            $connection->fetchAllNumeric('SELECT real_language_id, language_id FROM ibexa_object_state_group_language ORDER BY real_language_id')
        );
        self::assertEquals(
            [[1, 4, 'default'], [2, 2, 'default'], [2, 4, 'eng-GB'], [2, 5, 'fre-FR'], [3, 2, 'with bit']],
            $connection->fetchAllNumeric(
                'SELECT contentobject_state_id, language_id, name FROM ibexa_object_state_language ORDER BY contentobject_state_id, language_id'
            )
        );
    }
}
