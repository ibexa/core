<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Bundle\RepositoryInstaller\Migration;

use DateTimeImmutable;
use Doctrine\DBAL\Schema\Schema;
use Ibexa\Contracts\DoctrineMigrations\Migrations\AbstractSqlMigration;
use Ibexa\Contracts\DoctrineMigrations\Migrations\IbexaMigrationInterface;
use Ibexa\DoctrineMigrations\Migration\SqlPlatform;

/**
 * Adds "language_id" and "is_main_and_always_available" columns to
 * "ibexa_search_object_word_link", backfilled from its "language_mask" column (part of step 5 of
 * the language bitmask migration). Unlike other multilingual tables, this one only ever stored a
 * single language id per row (OR-ed with the always-available bit), so the replacement is a plain
 * id column plus a boolean flag rather than a join table. "language_mask" itself is dropped later,
 * once every write path has stopped populating it - see DropLanguageBitmaskColumnsMigration.
 *
 * Existing search index rows are backfilled here for consistency with every other step of this
 * migration, but the recommended (and, for a fully corrected index, mandatory) follow-up is a full
 * search reindex - the word-splitting/normalization logic itself is untouched, but this guarantees
 * newly indexed content never round-trips through the legacy bitmask columns.
 *
 * Guarded via the connection's schema manager rather than the injected $schema, because
 * TaggedMigrationsRunner (the "ibexa:install" path) invokes up() with an empty Schema, so
 * $schema->hasTable()/hasColumn() would always report false there.
 */
final class AddSearchObjectWordLinkLanguageIdColumnsMigration extends AbstractSqlMigration implements IbexaMigrationInterface
{
    private const TABLE = 'ibexa_search_object_word_link';
    private const LANGUAGE_ID_COLUMN = 'language_id';

    public function getDescription(): string
    {
        return 'Adds "language_id" and "is_main_and_always_available" columns to "ibexa_search_object_word_link", backfilled from the language mask';
    }

    public static function getTargetVersion(): string
    {
        return '6.0.0';
    }

    public static function getCreationDate(): DateTimeImmutable
    {
        return new DateTimeImmutable('2026-08-09 00:00:03');
    }

    public function up(Schema $schema): void
    {
        $this->abortIfUnsupportedPlatform(SqlPlatform::MYSQL, SqlPlatform::POSTGRESQL, SqlPlatform::SQLITE);

        $schemaManager = $this->connection->createSchemaManager();

        if (!$schemaManager->tablesExist([self::TABLE])) {
            return;
        }

        if ($schemaManager->introspectTable(self::TABLE)->hasColumn(self::LANGUAGE_ID_COLUMN)) {
            return;
        }

        if ($this->isMySQL()) {
            $this->addSqlFile(__DIR__ . '/sql/add-search-object-word-link-language-id-columns-mysql.sql');
        } elseif ($this->isPostgreSQL()) {
            $this->addSqlFile(__DIR__ . '/sql/add-search-object-word-link-language-id-columns-postgresql.sql');
        } elseif ($this->isSqlite()) {
            $this->addSqlFile(__DIR__ . '/sql/add-search-object-word-link-language-id-columns-sqlite.sql');
        }
    }
}
