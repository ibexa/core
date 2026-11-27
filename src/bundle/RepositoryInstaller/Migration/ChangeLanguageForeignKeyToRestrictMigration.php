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
use Ibexa\Contracts\DoctrineMigrations\Migrations\SqlPlatform;

/**
 * Changes the "ezcontent_language" foreign key of "ezcontentclass_attribute_ml" from ON DELETE CASCADE to
 * ON DELETE RESTRICT (IBX-13259), in line with the other tables referencing languages. Deleting a language
 * still in use there was already refused by the language persistence handler, so the database now refuses it
 * too. Guarded on the foreign key's ON DELETE action -- a no-op on a fresh install, which creates it with
 * RESTRICT already, and once applied.
 *
 * Nothing changes on SQLite, which can only change a constraint by rebuilding the table.
 */
final class ChangeLanguageForeignKeyToRestrictMigration extends AbstractSqlMigration implements IbexaMigrationInterface
{
    private const TABLE_NAME = 'ezcontentclass_attribute_ml';
    private const FOREIGN_KEY_NAME = 'ezcontentclass_attribute_ml_lang_fk';

    public function getDescription(): string
    {
        return 'Changes the "ezcontentclass_attribute_ml" foreign key to "ezcontent_language" to ON DELETE RESTRICT';
    }

    public static function getTargetVersion(): string
    {
        return '4.6.34';
    }

    public static function getCreationDate(): DateTimeImmutable
    {
        return new DateTimeImmutable('2026-11-20 00:00:00');
    }

    public function up(Schema $schema): void
    {
        $this->abortIfUnsupportedPlatform(SqlPlatform::MYSQL, SqlPlatform::MARIADB, SqlPlatform::POSTGRESQL, SqlPlatform::SQLITE);

        if (!$schema->hasTable(self::TABLE_NAME)) {
            return;
        }

        $table = $schema->getTable(self::TABLE_NAME);
        if (
            !$table->hasForeignKey(self::FOREIGN_KEY_NAME)
            || $table->getForeignKey(self::FOREIGN_KEY_NAME)->onDelete() !== 'CASCADE'
        ) {
            return;
        }

        if ($this->isMySQL() || $this->isMariaDB()) {
            $this->addSqlFile(__DIR__ . '/sql/change-contentclass-attribute-ml-language-fk-to-restrict-mysql.sql');
        } elseif ($this->isPostgreSQL()) {
            $this->addSqlFile(__DIR__ . '/sql/change-contentclass-attribute-ml-language-fk-to-restrict-postgresql.sql');
        }
    }
}
