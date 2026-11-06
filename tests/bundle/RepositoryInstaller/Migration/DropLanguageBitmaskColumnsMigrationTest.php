<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Bundle\RepositoryInstaller\Migration;

use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\Exception\AbortMigration;
use Doctrine\Migrations\Query\Query;
use Ibexa\Bundle\RepositoryInstaller\Migration\DropLanguageBitmaskColumnsMigration;
use Ibexa\Contracts\DoctrineMigrations\Migrations\MigrationRunContext;
use Ibexa\Tests\Core\Persistence\Legacy\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use Psr\Log\NullLogger;

#[CoversClass(DropLanguageBitmaskColumnsMigration::class)]
final class DropLanguageBitmaskColumnsMigrationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->insertDatabaseFixture(
            __DIR__ . '/../../../lib/Persistence/Legacy/Content/_fixtures/languages.php'
        );

        $connection = $this->getDatabaseConnection();
        $connection->executeStatement('ALTER TABLE ibexa_language RENAME TO ibexa_content_language');
        $connection->executeStatement('ALTER TABLE ibexa_content ADD COLUMN language_mask INTEGER DEFAULT 0 NOT NULL');
        $connection->executeStatement('ALTER TABLE ibexa_content_version ADD COLUMN language_mask INTEGER DEFAULT 0 NOT NULL');
        $connection->executeStatement('ALTER TABLE ibexa_url_alias_ml ADD COLUMN lang_mask INTEGER DEFAULT 0 NOT NULL');

        $connection->insert('ibexa_content', [
            'id' => 1,
            'content_type_id' => 1,
            'current_version' => 1,
            'initial_language_id' => 2,
            'language_mask' => 3,
            'name' => 'Foo',
            'owner_id' => 14,
            'remote_id' => 'foo',
        ]);
        $connection->insert('ibexa_content_translation', ['content_id' => 1, 'language_id' => 2]);
    }

    protected function tearDown(): void
    {
        $connection = $this->getDatabaseConnection();
        if ($connection->createSchemaManager()->tablesExist(['ibexa_content_language'])) {
            $connection->executeStatement('ALTER TABLE ibexa_content_language RENAME TO ibexa_language');
        }

        parent::tearDown();
    }

    public function testAbortsWhileAlwaysAvailableDisagreesWithTheMask(): void
    {
        $this->expectException(AbortMigration::class);
        $this->expectExceptionMessage('"always_available" disagreeing with bit 0 of the mask');

        $this->createMigration()->up(new Schema());
    }

    public function testDropsTheMasksOnceAlwaysAvailableMatches(): void
    {
        $this->getDatabaseConnection()->update(
            'ibexa_content',
            ['always_available' => true],
            ['id' => 1],
            ['always_available' => ParameterType::BOOLEAN]
        );

        $migration = $this->createMigration();
        $migration->up(new Schema());

        self::assertContains(
            'ALTER TABLE ibexa_content DROP COLUMN language_mask',
            array_map(static fn (Query $query): string => $query->getStatement(), $migration->getSql())
        );
    }

    private function createMigration(): DropLanguageBitmaskColumnsMigration
    {
        return new DropLanguageBitmaskColumnsMigration($this->getDatabaseConnection(), new NullLogger(), new MigrationRunContext());
    }
}
