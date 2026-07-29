<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Bundle\Core\Doctrine;

use Doctrine\DBAL\Schema\AbstractAsset;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @internal
 *
 * Doctrine ORM 3's schema-tool:update command always behaves as if
 * --complete was passed (see doctrine/orm UPGRADE.md), dropping any table
 * not backed by a registered ORM entity. Ibexa's legacy storage engine
 * schema (hundreds of tables, created outside of Doctrine ORM) shares the
 * same connections as ORM-mapped entities like TaxonomyEntry or
 * PaymentToken, so without this filter a scoped `doctrine:schema:update
 * --em=<name>` run wipes the legacy schema.
 *
 * Only tables backing a currently registered ORM entity are visible to
 * schema-tool's comparison. This deliberately does NOT also allow legacy
 * tables through: SchemaTool::createSchemaForComparison() builds its
 * "current state" side as `toSchema->hasTable($x) || $filter($x)` — a table
 * this filter allows is compared against the target schema, and any table
 * absent from the target (every legacy table, since the target here is
 * built only from this entity manager's own entities) gets scheduled for
 * DROP. Allowing a table through does not protect it; it does the opposite.
 * Denying it is what keeps it invisible to the comparison, and therefore
 * safe. Any code that needs to see whether an unmanaged table exists (e.g.
 * ibexa/migrations' own metadata table, or CoreInstaller's reinstall
 * drop-detection) must bypass this filter for that specific check rather
 * than rely on it - see CoreInstaller::getDropSqlStatementsForExistingSchema().
 */
final class ManagedTablesSchemaAssetFilter
{
    private ManagerRegistry $managerRegistry;

    /** @var array<string, true>|null */
    private ?array $managedTableNames = null;

    public function __construct(ManagerRegistry $managerRegistry)
    {
        $this->managerRegistry = $managerRegistry;
    }

    public function __invoke(string|AbstractAsset $asset): bool
    {
        $tableName = $asset instanceof AbstractAsset ? $asset->getName() : $asset;

        return isset($this->getManagedTableNames()[$tableName]);
    }

    /**
     * @return array<string, true>
     */
    private function getManagedTableNames(): array
    {
        if ($this->managedTableNames !== null) {
            return $this->managedTableNames;
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

        return $this->managedTableNames = $tableNames;
    }
}
