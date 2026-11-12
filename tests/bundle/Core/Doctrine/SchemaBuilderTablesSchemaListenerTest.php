<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Bundle\Core\Doctrine;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Platforms\MySQLPlatform;
use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\ForeignKeyConstraint;
use Doctrine\DBAL\Schema\ForeignKeyConstraint\ReferentialAction;
use Doctrine\DBAL\Schema\Index;
use Doctrine\DBAL\Schema\Index\IndexedColumn;
use Doctrine\DBAL\Schema\Index\IndexType;
use Doctrine\DBAL\Schema\Name\OptionallyQualifiedName;
use Doctrine\DBAL\Schema\Name\UnqualifiedName;
use Doctrine\DBAL\Schema\PrimaryKeyConstraint;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\Sequence;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\Event\GenerateSchemaEventArgs;
use Ibexa\Bundle\Core\Doctrine\SchemaBuilderTablesSchemaListener;
use Ibexa\Contracts\DoctrineSchema\Builder\SchemaBuilderInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use RuntimeException;

#[CoversClass(SchemaBuilderTablesSchemaListener::class)]
final class SchemaBuilderTablesSchemaListenerTest extends TestCase
{
    public function testAddsMissingTablesAndSequences(): void
    {
        $ibexaSchema = Schema::editor()
            ->addTable(
                Table::editor()
                    ->setUnquotedName('ibexa_legacy')
                    ->setColumns(
                        Column::editor()->setUnquotedName('id')->setTypeName(Types::INTEGER)->setAutoincrement(true)->create(),
                        Column::editor()->setUnquotedName('name')->setTypeName(Types::STRING)->setLength(50)->setNotNull(false)->create(),
                    )
                    ->addPrimaryKeyConstraint(
                        PrimaryKeyConstraint::editor()->setUnquotedColumnNames('id')->create()
                    )
                    ->addIndex(
                        Index::editor()
                            ->setUnquotedName('ibexa_legacy_name')
                            ->setType(IndexType::UNIQUE)
                            ->addUnquotedColumnName('name')
                    )
                    ->setOptions(['engine' => 'InnoDB'])
                    ->create()
            )
            ->addSequence(
                Sequence::editor()->setUnquotedName('ibexa_legacy_seq')->setAllocationSize(5)->setInitialValue(10)->create()
            )
            ->create();

        $target = Schema::editor()->create();
        $this->dispatch($this->createListener($ibexaSchema), $target, new PostgreSQLPlatform());

        self::assertTrue($target->hasTable('ibexa_legacy'));
        $copy = $target->getTable('ibexa_legacy');
        self::assertTrue($copy->hasColumn('id') && $copy->hasColumn('name'));
        self::assertSame(['id'], $this->toStrings($copy->getPrimaryKeyConstraint()?->getColumnNames() ?? []));
        self::assertTrue($copy->getIndex('ibexa_legacy_name')->isUnique());
        self::assertSame('InnoDB', $copy->getOption('engine'));
        self::assertTrue($target->hasSequence('ibexa_legacy_seq'));
        self::assertSame(5, $target->getSequence('ibexa_legacy_seq')->getAllocationSize());
        self::assertSame(10, $target->getSequence('ibexa_legacy_seq')->getInitialValue());
    }

    public function testLeavesTablesAndSequencesAlreadyInTargetAlone(): void
    {
        $ibexaSchema = Schema::editor()
            ->addTable($this->createTable('shared', 'from_ibexa'))
            ->addSequence(Sequence::editor()->setUnquotedName('shared_seq')->create())
            ->create();

        $target = Schema::editor()
            ->addTable($this->createTable('shared', 'from_orm'))
            ->addSequence(Sequence::editor()->setUnquotedName('shared_seq')->setAllocationSize(50)->create())
            ->create();

        $this->dispatch($this->createListener($ibexaSchema), $target, new PostgreSQLPlatform());

        self::assertTrue($target->getTable('shared')->hasColumn('from_orm'));
        self::assertFalse($target->getTable('shared')->hasColumn('from_ibexa'));
        self::assertSame(50, $target->getSequence('shared_seq')->getAllocationSize());
    }

    /**
     * @param array<int|null> $expectedLengths
     */
    #[DataProvider('provideIndexLengths')]
    public function testNormalizesIndexLengths(
        AbstractPlatform $platform,
        array $expectedLengths
    ): void {
        $ibexaSchema = Schema::editor()
            ->addTable(
                Table::editor()
                    ->setUnquotedName('indexed')
                    ->setColumns(
                        Column::editor()->setUnquotedName('name')->setTypeName(Types::STRING)->setLength(255)->create(),
                        Column::editor()->setUnquotedName('id2')->setTypeName(Types::INTEGER)->create(),
                    )
                    ->addIndex(
                        // real shape of ibexa_content_tree_contentobject_id_path_string in core's schema.yaml
                        Index::editor()
                            ->setUnquotedName('indexed_name')
                            ->addUnquotedColumnName('name', 191)
                            ->addUnquotedColumnName('id2')
                    )
                    ->create()
            )
            ->create();

        $target = Schema::editor()->create();
        $this->dispatch($this->createListener($ibexaSchema), $target, $platform);

        $lengths = array_map(
            static fn (IndexedColumn $column): ?int => $column->getLength(),
            $target->getTable('indexed')->getIndex('indexed_name')->getIndexedColumns()
        );
        self::assertSame($expectedLengths, $lengths);
    }

    /**
     * @return iterable<string, array{AbstractPlatform, array<int|null>}>
     */
    public static function provideIndexLengths(): iterable
    {
        yield 'platform without prefix indexes drops lengths' => [new PostgreSQLPlatform(), [null, null]];
        yield 'platform with prefix indexes keeps lengths as ints' => [new MySQLPlatform(), [191, null]];
    }

    public function testCopiesNumericDefaultsAsStrings(): void
    {
        $ibexaSchema = Schema::editor()
            ->addTable(
                Table::editor()
                    ->setUnquotedName('defaults')
                    ->setColumns(
                        Column::editor()->setUnquotedName('int_col')->setTypeName(Types::INTEGER)->setDefaultValue(0)->create(),
                        Column::editor()->setUnquotedName('float_col')->setTypeName(Types::FLOAT)->setDefaultValue(1.5)->create(),
                        Column::editor()->setUnquotedName('string_col')->setTypeName(Types::STRING)->setLength(5)->setDefaultValue('x')->create(),
                    )
                    ->create()
            )
            ->create();

        $target = Schema::editor()->create();
        $this->dispatch($this->createListener($ibexaSchema), $target, new PostgreSQLPlatform());

        $copy = $target->getTable('defaults');
        self::assertSame('0', $copy->getColumn('int_col')->getDefault());
        self::assertSame('1.5', $copy->getColumn('float_col')->getDefault());
        self::assertSame('x', $copy->getColumn('string_col')->getDefault());
    }

    public function testCopiesForeignKeyWithItsOptions(): void
    {
        $ibexaSchema = Schema::editor()
            ->addTable(
                Table::editor()
                    ->setUnquotedName('child')
                    ->setColumns(Column::editor()->setUnquotedName('parent_id')->setTypeName(Types::INTEGER)->create())
                    ->addForeignKeyConstraint(
                        ForeignKeyConstraint::editor()
                            ->setUnquotedName('fk_child_parent')
                            ->setUnquotedReferencingColumnNames('parent_id')
                            ->setReferencedTableName(OptionallyQualifiedName::unquoted('parent'))
                            ->setUnquotedReferencedColumnNames('id')
                            ->setOnDeleteAction(ReferentialAction::CASCADE)
                            ->setOnUpdateAction(ReferentialAction::SET_NULL)
                            ->create()
                    )
                    ->create()
            )
            ->create();

        $target = Schema::editor()->create();
        $this->dispatch($this->createListener($ibexaSchema), $target, new PostgreSQLPlatform());

        $foreignKey = $target->getTable('child')->getForeignKey('fk_child_parent');
        self::assertSame('parent', $foreignKey->getReferencedTableName()->toString());
        self::assertSame(['parent_id'], $this->toStrings($foreignKey->getReferencingColumnNames()));
        self::assertSame(['id'], $this->toStrings($foreignKey->getReferencedColumnNames()));
        self::assertSame(ReferentialAction::CASCADE, $foreignKey->getOnDeleteAction());
        self::assertSame(ReferentialAction::SET_NULL, $foreignKey->getOnUpdateAction());
    }

    public function testDoesNotRecurseWhileBuildingTheSchema(): void
    {
        $ibexaSchema = Schema::editor()->addTable($this->createTable('ibexa_legacy', 'id'))->create();

        // replaced below; the builder's callback needs a reference to the listener that owns the builder
        $listener = $this->createListener(Schema::editor()->create());
        $schemaBuilder = $this->createMock(SchemaBuilderInterface::class);
        $schemaBuilder
            ->expects($this->once())
            ->method('buildSchema')
            ->willReturnCallback(function () use ($ibexaSchema, &$listener): Schema {
                // what OrmEntitiesSchemaSubscriber -> SchemaTool does while the schema is built
                $this->dispatch($listener, Schema::editor()->create(), new PostgreSQLPlatform());

                return $ibexaSchema;
            });
        $listener = new SchemaBuilderTablesSchemaListener($schemaBuilder);

        $target = Schema::editor()->create();
        $this->dispatch($listener, $target, new PostgreSQLPlatform());

        self::assertTrue($target->hasTable('ibexa_legacy'));
    }

    public function testResetsRecursionGuardWhenBuildingTheSchemaFails(): void
    {
        $schemaBuilder = $this->createMock(SchemaBuilderInterface::class);
        $schemaBuilder
            ->expects($this->exactly(2))
            ->method('buildSchema')
            ->willReturnOnConsecutiveCalls(
                $this->throwException(new RuntimeException('build failed')),
                Schema::editor()->create()
            );
        $listener = new SchemaBuilderTablesSchemaListener($schemaBuilder);

        try {
            $this->dispatch($listener, Schema::editor()->create(), new PostgreSQLPlatform());
            self::fail('Expected the build failure to propagate');
        } catch (RuntimeException) {
            // expected
        }

        // a stuck guard would make this return early, without calling buildSchema() a second time
        $this->dispatch($listener, Schema::editor()->create(), new PostgreSQLPlatform());
    }

    public function testBuildsAgainOnTheNextGeneration(): void
    {
        $schemaBuilder = $this->createMock(SchemaBuilderInterface::class);
        $schemaBuilder
            ->expects($this->exactly(2))
            ->method('buildSchema')
            ->willReturn(Schema::editor()->create());
        $listener = new SchemaBuilderTablesSchemaListener($schemaBuilder);

        $this->dispatch($listener, Schema::editor()->create(), new PostgreSQLPlatform());
        $this->dispatch($listener, Schema::editor()->create(), new PostgreSQLPlatform());
    }

    /**
     * @param list<UnqualifiedName> $names
     *
     * @return list<string>
     */
    private function toStrings(array $names): array
    {
        return array_map(static fn (UnqualifiedName $name): string => $name->toString(), $names);
    }

    /**
     * @param non-empty-string $name
     * @param non-empty-string $columnName
     */
    private function createTable(
        string $name,
        string $columnName
    ): Table {
        return Table::editor()
            ->setUnquotedName($name)
            ->setColumns(Column::editor()->setUnquotedName($columnName)->setTypeName(Types::INTEGER)->create())
            ->create();
    }

    private function createListener(Schema $ibexaSchema): SchemaBuilderTablesSchemaListener
    {
        $schemaBuilder = self::createStub(SchemaBuilderInterface::class);
        $schemaBuilder->method('buildSchema')->willReturn($ibexaSchema);

        return new SchemaBuilderTablesSchemaListener($schemaBuilder);
    }

    private function dispatch(
        SchemaBuilderTablesSchemaListener $listener,
        Schema $target,
        AbstractPlatform $platform
    ): void {
        $connection = self::createStub(Connection::class);
        $connection->method('getDatabasePlatform')->willReturn($platform);
        $entityManager = self::createStub(EntityManagerInterface::class);
        $entityManager->method('getConnection')->willReturn($connection);

        $listener->postGenerateSchema(new GenerateSchemaEventArgs($entityManager, $target));
    }
}
