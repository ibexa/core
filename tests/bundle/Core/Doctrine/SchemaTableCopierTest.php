<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Bundle\Core\Doctrine;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Platforms\MySQLPlatform;
use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Types\Types;
use Ibexa\Bundle\Core\Doctrine\SchemaTableCopier;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(SchemaTableCopier::class)]
final class SchemaTableCopierTest extends TestCase
{
    public function testCopiesForeignKeyWithItsOptions(): void
    {
        $source = $this->createSourceTable();
        $source->addColumn('parent_id', Types::INTEGER);
        $source->addForeignKeyConstraint(
            'parent',
            ['parent_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => 'SET NULL'],
            'fk_child_parent'
        );

        $copy = $this->copy($source);

        $foreignKey = $copy->getForeignKey('fk_child_parent');
        self::assertSame('parent', $foreignKey->getForeignTableName());
        self::assertSame(['parent_id'], $foreignKey->getUnquotedLocalColumns());
        self::assertSame(['id'], $foreignKey->getUnquotedForeignColumns());
        self::assertSame('CASCADE', $foreignKey->onDelete());
        self::assertSame('SET NULL', $foreignKey->onUpdate());
    }

    #[DataProvider('provideTableOptionsCases')]
    public function testCopiesTableOptionsOnlyWhenNormalising(
        ?AbstractPlatform $platform,
        bool $expectCopied
    ): void {
        $source = $this->createSourceTable();
        $source->addOption('engine', 'InnoDB');

        $copy = $this->copy($source, $platform);

        self::assertSame($expectCopied, $copy->hasOption('engine'));
    }

    /**
     * @return iterable<string, array{?AbstractPlatform, bool}>
     */
    public static function provideTableOptionsCases(): iterable
    {
        yield 'without platform' => [null, false];
        yield 'with platform' => [new MySQLPlatform(), true];
    }

    public function testWithoutPlatformLeavesDefaultsAndIndexLengthsUntouched(): void
    {
        $source = $this->createSourceTable();
        $source->addColumn('int_col', Types::INTEGER, ['default' => 0]);
        $source->addColumn('path', Types::STRING, ['length' => 255]);
        $source->addColumn('id2', Types::INTEGER);
        $source->addIndex(['path', 'id2'], 'path_idx', [], ['lengths' => ['191', null]]);

        $copy = $this->copy($source);

        self::assertSame(0, $copy->getColumn('int_col')->getDefault());
        self::assertSame(['191', null], $copy->getIndex('path_idx')->getOptions()['lengths']);
    }

    public function testWithPlatformCastsMixedIndexLengthsKeepingNulls(): void
    {
        // real shape of ibexa_content_tree_contentobject_id_path_string in core's schema.yaml
        $source = $this->createSourceTable();
        $source->addColumn('path', Types::STRING, ['length' => 255]);
        $source->addColumn('id2', Types::INTEGER);
        $source->addIndex(['path', 'id2'], 'path_idx', [], ['lengths' => ['191', null]]);

        $copy = $this->copy($source, new MySQLPlatform());

        self::assertSame([191, null], $copy->getIndex('path_idx')->getOptions()['lengths']);
    }

    public function testWithPlatformWithoutPrefixIndexesDropsIndexLengths(): void
    {
        $source = $this->createSourceTable();
        $source->addColumn('path', Types::STRING, ['length' => 255]);
        $source->addIndex(['path'], 'path_idx', [], ['lengths' => ['191']]);

        $copy = $this->copy($source, new PostgreSQLPlatform());

        self::assertArrayNotHasKey('lengths', $copy->getIndex('path_idx')->getOptions());
    }

    private function createSourceTable(): Table
    {
        return (new Schema())->createTable('child');
    }

    private function copy(
        Table $source,
        ?AbstractPlatform $platform = null
    ): Table {
        $target = (new Schema())->createTable('child');
        SchemaTableCopier::copy($source, $target, $platform);

        return $target;
    }
}
