<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Bundle\Core\Doctrine;

use Doctrine\DBAL\Platforms\AbstractMySQLPlatform;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Schema\ColumnEditor;
use Doctrine\DBAL\Schema\Index\IndexedColumn;
use Doctrine\DBAL\Schema\Name\UnqualifiedName;
use Doctrine\DBAL\Schema\Table;
use Doctrine\ORM\Tools\Event\GenerateSchemaEventArgs;
use Ibexa\Contracts\DoctrineSchema\Builder\SchemaBuilderInterface;

/**
 * Adds every table and sequence known to Ibexa's SchemaBuilder (all packages' schema.yaml, plus
 * the ORM entities contributed to it) to the schema Doctrine ORM generates from its mappings
 * (ToolEvents::postGenerateSchema).
 *
 * Why: ORM 3 always behaves as if "doctrine:schema:update --complete" was passed, so any table
 * without an ORM entity behind it ends up in a DROP statement. Instead of hiding such tables from
 * all schema introspection of the connection with a schema assets filter, they are declared on the
 * "wanted" side of the comparison too, so SchemaTool and doctrine:migrations:diff see them on both
 * sides and leave them alone. This is the same pattern Symfony uses for the messenger, cache, lock
 * and session tables.
 *
 * Tables already in the generated schema are left alone: the ORM definition wins.
 *
 * @internal
 */
final class SchemaBuilderTablesSchemaListener
{
    private bool $building = false;

    public function __construct(private readonly SchemaBuilderInterface $schemaBuilder) {}

    public function postGenerateSchema(GenerateSchemaEventArgs $args): void
    {
        // buildSchema() -> OrmEntitiesSchemaSubscriber -> SchemaTool::getSchemaFromMetadata()
        // -> postGenerateSchema -> here again. Recursing would also clobber the SchemaBuilder's state.
        if ($this->building) {
            return;
        }

        $this->building = true;
        try {
            $ibexaSchema = $this->schemaBuilder->buildSchema();
        } finally {
            $this->building = false;
        }

        $target = $args->getSchema();
        $platform = $args->getEntityManager()->getConnection()->getDatabasePlatform();

        $editor = $target->edit();

        foreach ($ibexaSchema->getTables() as $table) {
            if (!$target->hasTable($table->getObjectName()->toString())) {
                $editor->addTable($this->normalizeForIntrospection($table, $platform));
            }
        }

        foreach ($ibexaSchema->getSequences() as $sequence) {
            if (!$target->hasSequence($sequence->getObjectName()->toString())) {
                $editor->addSequence($sequence);
            }
        }

        $args->setSchema($editor->create());
    }

    /**
     * Makes the table compare equal to what schema introspection returns on the platform, which
     * changes two things: int/float column defaults become strings (schema.yaml declares them as
     * numbers, introspection returns strings), and index column lengths are dropped when the
     * platform has no prefix indexes (introspection never returns them there, yet
     * Index::hasSameColumnLengths() compares them). Table options (engine, charset...) are kept.
     */
    private function normalizeForIntrospection(
        Table $table,
        AbstractPlatform $platform
    ): Table {
        $editor = $table->edit();

        foreach ($table->getColumns() as $column) {
            $default = $column->getDefault();
            if (is_int($default) || is_float($default)) {
                $editor->modifyColumn(
                    $column->getObjectName(),
                    static function (ColumnEditor $columnEditor) use ($default): void {
                        $columnEditor->setDefaultValue((string)$default);
                    }
                );
            }
        }

        foreach ($table->getIndexes() as $index) {
            if (!$this->hasColumnLengths($index->getIndexedColumns())) {
                continue;
            }

            // rebuilt from the indexed columns, which hold the lengths as ints, whereas the
            // index's raw options keep them as declared in schema.yaml (['191'])
            $indexEditor = $index->edit();
            // only MySQL/MariaDB support prefix indexes (AbstractPlatform::supportsColumnLengthIndexes()
            // is deprecated without a replacement, see doctrine/dbal#6886)
            if (!$platform instanceof AbstractMySQLPlatform) {
                $indexEditor->setColumnNames(...array_map(
                    static fn (IndexedColumn $column): UnqualifiedName => $column->getColumnName(),
                    $index->getIndexedColumns()
                ));
            }

            $editor->dropIndex($index->getObjectName())->addIndex($indexEditor->create());
        }

        return $editor->create();
    }

    /**
     * @param list<IndexedColumn> $columns
     */
    private function hasColumnLengths(array $columns): bool
    {
        foreach ($columns as $column) {
            if ($column->getLength() !== null) {
                return true;
            }
        }

        return false;
    }
}
