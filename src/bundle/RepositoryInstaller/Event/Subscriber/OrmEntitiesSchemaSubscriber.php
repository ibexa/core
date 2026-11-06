<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Bundle\RepositoryInstaller\Event\Subscriber;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Tools\SchemaTool;
use Ibexa\Bundle\Core\Doctrine\SchemaTableCopier;
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
            static fn (string $class): ClassMetadata => $metadataFactory->getMetadataFor($class),
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
            static fn (ClassMetadata $metadata): string => $metadata->getTableName(),
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

            SchemaTableCopier::copy($ormTable, $targetSchema->createTable($ormTable->getName()));
        }

        $ownSequenceNames = array_filter(array_map(
            static fn (ClassMetadata $metadata): ?string => $metadata->sequenceGeneratorDefinition['sequenceName'] ?? null,
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
}
