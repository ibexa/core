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
 * Adds ON UPDATE NO ACTION to the "ibexa_content_bookmark" foreign keys on MariaDB, as a fresh 5.0
 * install has them.
 *
 * The schema declares both with ON UPDATE NO ACTION, and {@see InstallSchemaMigration}'s SQL leaves
 * it out, as it's the default. They behave the same, but MariaDB reports a foreign key without it
 * as RESTRICT, so the two installs differ in information_schema and in Doctrine DBAL's schema
 * comparison. MySQL and PostgreSQL report NO ACTION either way. MariaDB can't change a foreign key,
 * so each is dropped and added again. Both always have the same rule, so only the first one is
 * checked.
 */
final class RecreateMariaDbBookmarkForeignKeysMigration extends AbstractSqlMigration implements IbexaMigrationInterface
{
    public function getDescription(): string
    {
        return 'Adds ON UPDATE NO ACTION to the core bookmark foreign keys on MariaDB';
    }

    public static function getTargetVersion(): string
    {
        return '5.0.0';
    }

    public static function getCreationDate(): DateTimeImmutable
    {
        return new DateTimeImmutable('2026-10-06 00:00:02');
    }

    public function up(Schema $schema): void
    {
        $this->abortIfUnsupportedPlatform(SqlPlatform::MYSQL, SqlPlatform::MARIADB, SqlPlatform::POSTGRESQL, SqlPlatform::SQLITE);

        if (!$this->isMariaDB()) {
            return;
        }

        $updateRule = $this->connection->fetchOne(
            'SELECT UPDATE_RULE FROM information_schema.REFERENTIAL_CONSTRAINTS WHERE CONSTRAINT_SCHEMA = DATABASE()'
            . " AND TABLE_NAME = 'ibexa_content_bookmark' AND CONSTRAINT_NAME = 'ibexa_content_bookmark_location_fk'"
        );
        if ($updateRule === 'RESTRICT') {
            $this->addSqlFile(__DIR__ . '/sql/recreate-bookmark-foreign-keys-mariadb.sql');
        }
    }
}
