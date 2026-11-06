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
use Doctrine\DBAL\Schema\Schema;
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
    public function testCopiesMissingTablesAndSequences(): void
    {
        $ibexaSchema = new Schema();
        $table = $ibexaSchema->createTable('ibexa_legacy');
        $table->addColumn('id', Types::INTEGER, ['autoincrement' => true]);
        $table->addColumn('name', Types::STRING, ['length' => 50, 'notnull' => false]);
        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['name'], 'ibexa_legacy_name');
        $ibexaSchema->createSequence('ibexa_legacy_seq', 5, 10);

        $target = new Schema();
        $this->dispatch($this->createListener($ibexaSchema), $target, new PostgreSQLPlatform());

        self::assertTrue($target->hasTable('ibexa_legacy'));
        $copy = $target->getTable('ibexa_legacy');
        self::assertTrue($copy->hasColumn('id') && $copy->hasColumn('name'));
        self::assertSame(['id'], $copy->getPrimaryKey()?->getColumns());
        self::assertTrue($copy->getIndex('ibexa_legacy_name')->isUnique());
        self::assertTrue($target->hasSequence('ibexa_legacy_seq'));
        self::assertSame(5, $target->getSequence('ibexa_legacy_seq')->getAllocationSize());
        self::assertSame(10, $target->getSequence('ibexa_legacy_seq')->getInitialValue());
    }

    public function testLeavesTablesAndSequencesAlreadyInTargetAlone(): void
    {
        $ibexaSchema = new Schema();
        $ibexaSchema->createTable('shared')->addColumn('from_ibexa', Types::INTEGER);
        $ibexaSchema->createSequence('shared_seq', 1, 1);

        $target = new Schema();
        $target->createTable('shared')->addColumn('from_orm', Types::INTEGER);
        $target->createSequence('shared_seq', 50, 1);

        $this->dispatch($this->createListener($ibexaSchema), $target, new PostgreSQLPlatform());

        self::assertTrue($target->getTable('shared')->hasColumn('from_orm'));
        self::assertFalse($target->getTable('shared')->hasColumn('from_ibexa'));
        self::assertSame(50, $target->getSequence('shared_seq')->getAllocationSize());
    }

    /**
     * @param array<int|null>|null $expectedLengths
     */
    #[DataProvider('provideIndexLengths')]
    public function testNormalizesIndexLengths(
        AbstractPlatform $platform,
        ?array $expectedLengths
    ): void {
        $ibexaSchema = new Schema();
        $table = $ibexaSchema->createTable('indexed');
        $table->addColumn('name', Types::STRING, ['length' => 255]);
        $table->addIndex(['name'], 'indexed_name', [], ['lengths' => ['191']]);

        $target = new Schema();
        $this->dispatch($this->createListener($ibexaSchema), $target, $platform);

        self::assertSame($expectedLengths, $target->getTable('indexed')->getIndex('indexed_name')->getOptions()['lengths'] ?? null);
    }

    /**
     * @return iterable<string, array{AbstractPlatform, array<int|null>|null}>
     */
    public static function provideIndexLengths(): iterable
    {
        yield 'platform without prefix indexes drops lengths' => [new PostgreSQLPlatform(), null];
        yield 'platform with prefix indexes casts lengths to int' => [new MySQLPlatform(), [191]];
    }

    public function testCopiesNumericDefaultsAsStrings(): void
    {
        $ibexaSchema = new Schema();
        $table = $ibexaSchema->createTable('defaults');
        $table->addColumn('int_col', Types::INTEGER, ['default' => 0]);
        $table->addColumn('float_col', Types::FLOAT, ['default' => 1.5]);
        $table->addColumn('string_col', Types::STRING, ['length' => 5, 'default' => 'x']);

        $target = new Schema();
        $this->dispatch($this->createListener($ibexaSchema), $target, new PostgreSQLPlatform());

        $copy = $target->getTable('defaults');
        self::assertSame('0', $copy->getColumn('int_col')->getDefault());
        self::assertSame('1.5', $copy->getColumn('float_col')->getDefault());
        self::assertSame('x', $copy->getColumn('string_col')->getDefault());
    }

    public function testDoesNotRecurseWhileBuildingTheSchema(): void
    {
        $ibexaSchema = new Schema();
        $ibexaSchema->createTable('ibexa_legacy')->addColumn('id', Types::INTEGER);

        // replaced below; the builder's callback needs a reference to the listener that owns the builder
        $listener = $this->createListener(new Schema());
        $schemaBuilder = $this->createMock(SchemaBuilderInterface::class);
        $schemaBuilder
            ->expects($this->once())
            ->method('buildSchema')
            ->willReturnCallback(function () use ($ibexaSchema, &$listener): Schema {
                // what OrmEntitiesSchemaSubscriber -> SchemaTool does while the schema is built
                $this->dispatch($listener, new Schema(), new PostgreSQLPlatform());

                return $ibexaSchema;
            });
        $listener = new SchemaBuilderTablesSchemaListener($schemaBuilder);

        $target = new Schema();
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
                new Schema()
            );
        $listener = new SchemaBuilderTablesSchemaListener($schemaBuilder);

        try {
            $this->dispatch($listener, new Schema(), new PostgreSQLPlatform());
            self::fail('Expected the build failure to propagate');
        } catch (RuntimeException) {
            // expected
        }

        // a stuck guard would make this return early, without calling buildSchema() a second time
        $this->dispatch($listener, new Schema(), new PostgreSQLPlatform());
    }

    public function testBuildsAgainOnTheNextGeneration(): void
    {
        $schemaBuilder = $this->createMock(SchemaBuilderInterface::class);
        $schemaBuilder
            ->expects($this->exactly(2))
            ->method('buildSchema')
            ->willReturn(new Schema());
        $listener = new SchemaBuilderTablesSchemaListener($schemaBuilder);

        $this->dispatch($listener, new Schema(), new PostgreSQLPlatform());
        $this->dispatch($listener, new Schema(), new PostgreSQLPlatform());
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
