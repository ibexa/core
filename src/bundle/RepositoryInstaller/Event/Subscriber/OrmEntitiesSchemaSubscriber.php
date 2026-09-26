<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Bundle\RepositoryInstaller\Event\Subscriber;

use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Ibexa\Contracts\DoctrineSchema\Event\SchemaBuilderEvent;
use Ibexa\Contracts\DoctrineSchema\SchemaBuilderEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Contributes the tables of plain Doctrine ORM entities to the SchemaBuilderEvent schema by
 * introspecting their mappings, instead of a hand-maintained parallel schema.yaml declaration --
 * the ORM mapping is already the single source of truth for what these tables look like, and
 * SchemaTool already knows how to turn mapping metadata into a Doctrine\DBAL\Schema\Schema.
 *
 * A package registers its own instance, listing only its own entities:
 *
 *     ibexa.<package>.schema_builder.orm_entities:
 *         class: Ibexa\Bundle\RepositoryInstaller\Event\Subscriber\OrmEntitiesSchemaSubscriber
 *         arguments:
 *             $entityManager: '@ibexa.doctrine.orm.entity_manager'
 *             $entityClasses:
 *                 - Ibexa\<Package>\Persistence\Entity\SomeEntity
 *         tags: [kernel.event_subscriber]
 *
 * Tables already present in the schema are left as they are.
 */
final readonly class OrmEntitiesSchemaSubscriber implements EventSubscriberInterface
{
    /**
     * @param list<class-string> $entityClasses
     */
    public function __construct(
        private EntityManagerInterface $entityManager,
        private array $entityClasses
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [
            SchemaBuilderEvents::BUILD_SCHEMA => ['onBuildSchema', 250],
        ];
    }

    public function onBuildSchema(SchemaBuilderEvent $event): void
    {
        $metadataFactory = $this->entityManager->getMetadataFactory();
        $classMetadata = array_map(
            static fn (string $class) => $metadataFactory->getMetadataFor($class),
            $this->entityClasses,
        );

        $schemaTool = new SchemaTool($this->entityManager);
        $ormSchema = $schemaTool->getSchemaFromMetadata($classMetadata);

        // SchemaTool::getSchemaFromMetadata() fires Doctrine's ToolEvents::postGenerateSchema on
        // the entity manager's (shared) event manager -- any *other* listener on that same event
        // manager (e.g. Symfony Messenger's Doctrine transport auto-setup) reacts too and adds its
        // own table, regardless of which classes were actually requested here. Only transplant the
        // tables that genuinely belong to the requested entities.
        $ownTableNames = array_map(
            static fn ($metadata) => $metadata->getTableName(),
            $classMetadata,
        );

        $targetSchema = $event->getSchema();
        foreach ($ormSchema->getTables() as $ormTable) {
            if (!in_array($ormTable->getName(), $ownTableNames, true)) {
                continue;
            }

            if ($targetSchema->hasTable($ormTable->getName())) {
                continue;
            }

            $this->copyTable($ormTable, $targetSchema->createTable($ormTable->getName()));
        }

        $ownSequenceNames = array_filter(array_map(
            static fn ($metadata) => $metadata->sequenceGeneratorDefinition['sequenceName'] ?? null,
            $classMetadata,
        ));

        foreach ($ormSchema->getSequences() as $sequence) {
            if (!in_array($sequence->getName(), $ownSequenceNames, true)) {
                continue;
            }

            if ($targetSchema->hasSequence($sequence->getName())) {
                continue;
            }

            $targetSchema->createSequence(
                $sequence->getName(),
                $sequence->getAllocationSize(),
                $sequence->getInitialValue(),
            );
        }
    }

    /**
     * Doctrine\DBAL\Schema\Schema has no public API to transplant an existing Table between two
     * Schema instances (Schema::_addTable() is protected), so each aspect is copied individually
     * through Table's own public API -- the same approach ibexa/doctrine-schema's own
     * SchemaImporter uses when building a table from a parsed Yaml array, just reading from an
     * already-built Table here instead.
     */
    private function copyTable(
        Table $source,
        Table $target
    ): void {
        foreach ($source->getColumns() as $column) {
            $options = [
                'length' => $column->getLength(),
                'precision' => $column->getPrecision(),
                'scale' => $column->getScale(),
                'unsigned' => $column->getUnsigned(),
                'fixed' => $column->getFixed(),
                'notnull' => $column->getNotnull(),
                'default' => $column->getDefault(),
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

            if ($index->isUnique()) {
                $target->addUniqueIndex($index->getUnquotedColumns(), $index->getName(), $index->getOptions());
            } else {
                $target->addIndex($index->getUnquotedColumns(), $index->getName(), $index->getFlags(), $index->getOptions());
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
    }
}
