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
 * Creates "ibexa_content_translation", "ibexa_content_version_translation" and
 * "ibexa_url_alias_ml_translation" - the relational replacement for the
 * "language_mask"/"lang_mask" columns on "ibexa_content", "ibexa_content_version" and
 * "ibexa_url_alias_ml" (step 2 of the language bitmask migration).
 *
 * Purely additive: tables are created empty and stay empty until
 * "ibexa:languages:backfill-translations" populates them, and nothing reads them yet - the mask
 * columns remain authoritative until later steps switch read paths over and eventually drop them.
 *
 * Guarded via the connection's schema manager rather than the injected $schema, because
 * TaggedMigrationsRunner (the "ibexa:install" path) invokes up() with an empty Schema, so
 * $schema->hasTable() would always report false there.
 */
final class AddLanguageTranslationTablesMigration extends AbstractSqlMigration implements IbexaMigrationInterface
{
    private const CONTENT_TABLE = 'ibexa_content';
    private const CONTENT_TRANSLATION_TABLE = 'ibexa_content_translation';

    public function getDescription(): string
    {
        return 'Creates "ibexa_content_translation", "ibexa_content_version_translation" and "ibexa_url_alias_ml_translation"';
    }

    public static function getTargetVersion(): string
    {
        return '6.0.0';
    }

    public static function getCreationDate(): DateTimeImmutable
    {
        return new DateTimeImmutable('2026-08-09 00:00:01');
    }

    public function up(Schema $schema): void
    {
        $this->abortIfUnsupportedPlatform(SqlPlatform::MYSQL, SqlPlatform::POSTGRESQL, SqlPlatform::SQLITE);

        $schemaManager = $this->connection->createSchemaManager();

        if (!$schemaManager->tablesExist([self::CONTENT_TABLE])) {
            return;
        }

        if ($schemaManager->tablesExist([self::CONTENT_TRANSLATION_TABLE])) {
            return;
        }

        if ($this->isMySQL()) {
            $this->addSqlFile(__DIR__ . '/sql/add-language-translation-tables-mysql.sql');
        } elseif ($this->isPostgreSQL()) {
            $this->addSqlFile(__DIR__ . '/sql/add-language-translation-tables-postgresql.sql');
        } elseif ($this->isSqlite()) {
            $this->addSqlFile(__DIR__ . '/sql/add-language-translation-tables-sqlite.sql');
        }
    }
}
