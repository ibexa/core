<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Bundle\Core\Doctrine;

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

        foreach ($ibexaSchema->getTables() as $table) {
            if (!$target->hasTable($table->getName())) {
                SchemaTableCopier::copy($table, $target->createTable($table->getName()), $platform);
            }
        }

        foreach ($ibexaSchema->getSequences() as $sequence) {
            if (!$target->hasSequence($sequence->getName())) {
                $target->createSequence($sequence->getName(), $sequence->getAllocationSize(), $sequence->getInitialValue());
            }
        }
    }
}
