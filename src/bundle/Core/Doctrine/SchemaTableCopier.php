<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Bundle\Core\Doctrine;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Types\Type;

/**
 * Doctrine\DBAL\Schema\Schema has no public API to transplant an existing Table between two
 * Schema instances (Schema::_addTable() is protected), so each aspect is copied individually
 * through Table's own public API.
 *
 * @internal
 */
final class SchemaTableCopier
{
    /**
     * @param AbstractPlatform|null $comparedWithIntrospectionOn when given, the copy is
     *        normalised so that it compares equal to what schema introspection returns on this platform
     *        (and the table options are copied too). Without it the table is copied verbatim.
     */
    public static function copy(
        Table $source,
        Table $target,
        ?AbstractPlatform $comparedWithIntrospectionOn = null
    ): void {
        $normalize = $comparedWithIntrospectionOn !== null;

        foreach ($source->getColumns() as $column) {
            $default = $column->getDefault();
            $options = [
                'length' => $column->getLength(),
                'precision' => $column->getPrecision(),
                'scale' => $column->getScale(),
                'unsigned' => $column->getUnsigned(),
                'fixed' => $column->getFixed(),
                'notnull' => $column->getNotnull(),
                // schema.yaml declares int/float defaults, introspection returns strings
                'default' => $normalize && (is_int($default) || is_float($default)) ? (string)$default : $default,
                'autoincrement' => $column->getAutoincrement(),
                'columnDefinition' => $column->getColumnDefinition(),
                'comment' => $column->getComment(),
            ];
            if ($column->getPlatformOptions() !== []) {
                $options['platformOptions'] = $column->getPlatformOptions();
            }
            $target->addColumn($column->getName(), Type::getTypeRegistry()->lookupName($column->getType()), $options);
        }

        $primaryKey = $source->getPrimaryKey();
        if ($primaryKey !== null) {
            $target->setPrimaryKey($primaryKey->getUnquotedColumns());
        }

        foreach ($source->getIndexes() as $index) {
            if ($index->isPrimary()) {
                continue;
            }

            $indexOptions = $index->getOptions();
            if ($normalize && isset($indexOptions['lengths'])) {
                if (!$comparedWithIntrospectionOn->supportsColumnLengthIndexes()) {
                    // introspection never returns lengths there, yet Index::hasSameColumnLengths() compares them
                    unset($indexOptions['lengths']);
                } else {
                    // schema.yaml declares ['191'], introspection returns [191]; compared with ===
                    $indexOptions['lengths'] = array_map(
                        static fn ($length): ?int => $length === null ? null : (int)$length,
                        $indexOptions['lengths']
                    );
                }
            }

            if ($index->isUnique()) {
                $target->addUniqueIndex($index->getUnquotedColumns(), $index->getName(), $indexOptions);
            } else {
                $target->addIndex($index->getUnquotedColumns(), $index->getName(), $index->getFlags(), $indexOptions);
            }
        }

        foreach ($source->getForeignKeys() as $foreignKey) {
            $target->addForeignKeyConstraint(
                $foreignKey->getForeignTableName(),
                array_values($foreignKey->getUnquotedLocalColumns()),
                array_values($foreignKey->getUnquotedForeignColumns()),
                $foreignKey->getOptions(),
                $foreignKey->getName(),
            );
        }

        if ($normalize) {
            foreach ($source->getOptions() as $name => $value) {
                $target->addOption($name, $value);
            }
        }
    }
}
