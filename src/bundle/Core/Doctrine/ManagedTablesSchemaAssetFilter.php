<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Bundle\Core\Doctrine;

use Doctrine\DBAL\Schema\AbstractAsset;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Ibexa\Contracts\DoctrineSchema\Builder\SchemaBuilderInterface;

/**
 * @internal
 *
 * Doctrine ORM 3's schema-tool:update command always behaves as if
 * --complete was passed (see doctrine/orm UPGRADE.md), dropping any table
 * not backed by a registered ORM entity or otherwise known to Doctrine.
 * Ibexa's legacy storage engine schema (hundreds of tables, created outside
 * of Doctrine ORM, declared instead through ibexa/doctrine-schema's
 * event-driven SchemaBuilder) shares the same connections as ORM-mapped
 * entities like TaxonomyEntry or PaymentToken, so without this filter a
 * scoped `doctrine:schema:update --em=<name>` run wipes the legacy schema.
 *
 * A table is left available for Doctrine to manage (and thus to drop or
 * alter) only if it backs a currently registered ORM entity, or if it's one
 * of the legacy tables every bundle declares through SchemaBuilder — which
 * also covers non-ORM tables managed by hand outside of schema:update
 * entirely, like ibexa/migrations' own tracking table, since it declares
 * itself the same way. Everything genuinely unknown to Ibexa is left
 * untouched.
 */
final class ManagedTablesSchemaAssetFilter
{
    private ManagerRegistry $managerRegistry;

    private SchemaBuilderInterface $schemaBuilder;

    /** @var array<string, true>|null */
    private ?array $ormEntityTableNames = null;

    private ?Schema $legacySchema = null;

    public function __construct(ManagerRegistry $managerRegistry, SchemaBuilderInterface $schemaBuilder)
    {
        $this->managerRegistry = $managerRegistry;
        $this->schemaBuilder = $schemaBuilder;
    }

    public function __invoke(string|AbstractAsset $asset): bool
    {
        $tableName = $asset instanceof AbstractAsset ? $asset->getName() : $asset;

        return isset($this->getOrmEntityTableNames()[$tableName])
            || $this->getLegacySchema()->hasTable($tableName);
    }

    /**
     * @return array<string, true>
     */
    private function getOrmEntityTableNames(): array
    {
        if ($this->ormEntityTableNames !== null) {
            return $this->ormEntityTableNames;
        }

        $tableNames = [];
        foreach ($this->managerRegistry->getManagers() as $manager) {
            if (!$manager instanceof EntityManagerInterface) {
                continue;
            }

            foreach ($manager->getMetadataFactory()->getAllMetadata() as $classMetadata) {
                $tableNames[$classMetadata->getTableName()] = true;
            }
        }

        return $this->ormEntityTableNames = $tableNames;
    }

    private function getLegacySchema(): Schema
    {
        return $this->legacySchema ??= $this->schemaBuilder->buildSchema();
    }
}
