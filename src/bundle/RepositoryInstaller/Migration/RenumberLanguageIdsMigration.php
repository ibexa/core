<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Bundle\RepositoryInstaller\Migration;

use DateTimeImmutable;
use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Schema\ForeignKeyConstraint\ReferentialAction;
use Doctrine\DBAL\Schema\Schema;
use Ibexa\Contracts\DoctrineMigrations\Migrations\AbstractSqlMigration;
use Ibexa\Contracts\DoctrineMigrations\Migrations\IbexaMigrationInterface;
use Ibexa\Contracts\DoctrineMigrations\Migrations\SqlPlatform;

/**
 * Renumbers the languages 2, 3, 4… in the order of their ids, as a fresh install numbers them.
 */
final class RenumberLanguageIdsMigration extends AbstractSqlMigration implements IbexaMigrationInterface
{
    private const string LANGUAGE_TABLE = 'ibexa_content_language';

    private const string LANGUAGE_ID_COLUMN_PATTERN = '/(?:^|_)language_id$/';

    private const array RENUMBERED_WITHOUT_FOREIGN_KEYS = [
        'ibexa_content.initial_language_id',
        'ibexa_content_field.language_id',
        'ibexa_content_name.language_id',
        'ibexa_content_translation.language_id',
        'ibexa_content_type.initial_language_id',
        'ibexa_content_type_field_definition_ml.language_id',
        'ibexa_content_type_name.language_id',
        'ibexa_content_version.initial_language_id',
        'ibexa_content_version_translation.language_id',
        'ibexa_object_state.default_language_id',
        'ibexa_object_state_group.default_language_id',
        'ibexa_object_state_group_language.language_id',
        'ibexa_object_state_group_language.real_language_id',
        'ibexa_object_state_language.language_id',
        'ibexa_search_object_word_link.language_id',
        'ibexa_url_alias_ml_translation.language_id',
    ];

    public function getDescription(): string
    {
        return 'Renumbers the languages 2, 3, 4… in the order of their ids';
    }

    public static function getTargetVersion(): string
    {
        return '6.0.0';
    }

    public static function getCreationDate(): DateTimeImmutable
    {
        return new DateTimeImmutable('2026-11-17 00:00:00');
    }

    public function isTransactional(): bool
    {
        return true;
    }

    public function up(Schema $schema): void
    {
        $this->abortIfUnsupportedPlatform(SqlPlatform::MYSQL, SqlPlatform::MARIADB, SqlPlatform::POSTGRESQL, SqlPlatform::SQLITE);

        $schemaManager = $this->connection->createSchemaManager();
        if (!$schemaManager->tablesExist([self::LANGUAGE_TABLE])) {
            return;
        }

        $ids = array_map('intval', $this->connection->fetchFirstColumn('SELECT id FROM ' . self::LANGUAGE_TABLE . ' ORDER BY id'));
        if ($ids === [] || $ids === range(2, count($ids) + 1)) {
            return;
        }

        $columns = $this->introspectLanguageIdColumns();

        if ($this->isSqlite() && !(bool)$this->connection->fetchOne('PRAGMA foreign_keys')) {
            $this->abortIfLeftBehind(
                array_diff(array_keys($columns), self::RENUMBERED_WITHOUT_FOREIGN_KEYS),
                'SQLite doesn\'t enforce foreign keys on this connection, so only the core columns get renumbered. Run this migration with PRAGMA foreign_keys = ON.'
            );
            $this->addSqlFile(__DIR__ . '/sql/renumber-language-ids-without-foreign-keys-sqlite.sql');

            return;
        }

        $this->abortIfLeftBehind(
            array_keys(array_filter($columns, static fn (bool $cascades): bool => !$cascades)),
            sprintf(
                'Add a foreign key to "%s" with ON UPDATE CASCADE to each of them, in a migration that runs before this one.',
                self::LANGUAGE_TABLE
            )
        );

        $setsAsideStateGroupTranslations = $this->isMySQL() || $this->isMariaDB();
        if ($setsAsideStateGroupTranslations) {
            $this->addSqlFile(__DIR__ . '/sql/renumber-language-ids-set-aside-mysql.sql');
        }

        foreach ($ids as $index => $oldId) {
            $newId = $index + 2;
            if ($newId !== $oldId) {
                $this->addSql(
                    'UPDATE ' . self::LANGUAGE_TABLE . ' SET id = :new_id WHERE id = :old_id',
                    ['new_id' => $newId, 'old_id' => $oldId],
                    ['new_id' => ParameterType::INTEGER, 'old_id' => ParameterType::INTEGER]
                );
            }
        }

        if ($setsAsideStateGroupTranslations) {
            $this->addSqlFile(__DIR__ . '/sql/renumber-language-ids-restore-mysql.sql');
        }
    }

    /**
     * Maps each language id column, and each column with a foreign key to the language table, to whether its foreign keys cascade updates.
     *
     * @return array<non-empty-string, bool>
     */
    private function introspectLanguageIdColumns(): array
    {
        $columns = [];
        foreach ($this->connection->createSchemaManager()->introspectTables() as $table) {
            $tableName = $table->getObjectName()->getUnqualifiedName()->getValue();

            foreach ($table->getForeignKeys() as $foreignKey) {
                if ($foreignKey->getReferencedTableName()->getUnqualifiedName()->getValue() !== self::LANGUAGE_TABLE) {
                    continue;
                }

                foreach ($foreignKey->getReferencingColumnNames() as $column) {
                    $name = $tableName . '.' . $column->getIdentifier()->getValue();
                    $columns[$name] = ($columns[$name] ?? true)
                        && $foreignKey->getOnUpdateAction() === ReferentialAction::CASCADE;
                }
            }

            foreach ($table->getColumns() as $column) {
                $columnName = $column->getObjectName()->getIdentifier()->getValue();
                if (preg_match(self::LANGUAGE_ID_COLUMN_PATTERN, $columnName) === 1) {
                    $columns[$tableName . '.' . $columnName] ??= false;
                }
            }
        }

        return $columns;
    }

    /**
     * @param array<int, non-empty-string> $columns
     */
    private function abortIfLeftBehind(
        array $columns,
        string $remedy
    ): void {
        $this->abortIf(
            $columns !== [],
            sprintf(
                'Renumbering the languages would leave the old ids, which then belong to other languages, in %s. %s',
                implode(', ', $columns),
                $remedy
            )
        );
    }
}
