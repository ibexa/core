<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Integration\Core\Doctrine;

use Doctrine\Bundle\DoctrineBundle\Dbal\SchemaAssetsFilterManager;
use Doctrine\DBAL\Configuration;
use Ibexa\Contracts\Core\Test\IbexaKernelTestCase;

/**
 * Both InjectEntityManagerMappingsPass and DoctrineBundle's DbalSchemaFilterPass install a
 * schema assets filter on the very same DBAL connection Configuration. Since
 * Configuration::setSchemaAssetsFilter() overwrites rather than composes, this verifies in a
 * fully booted kernel - i.e. with the real compiler pass ordering - that neither the legacy
 * schema protection nor a project's own schema filter gets lost.
 *
 * @covers \Ibexa\Bundle\Core\DependencyInjection\Compiler\InjectEntityManagerMappingsPass
 */
final class ManagedTablesSchemaAssetFilterCompositionTest extends IbexaKernelTestCase
{
    protected static function getKernelClass(): string
    {
        return SchemaAssetsFilterTestKernel::class;
    }

    public function testFiltersAreComposedInsteadOfOverwritten(): void
    {
        self::assertInstanceOf(SchemaAssetsFilterManager::class, $this->getSchemaAssetsFilter());
    }

    public function testAllowsTableBackedByAnOrmEntity(): void
    {
        $filter = $this->getSchemaAssetsFilter();

        self::assertTrue($filter(SchemaAssetsFilterTestKernel::MANAGED_TABLE));
    }

    public function testProtectsLegacyTableFromOrmSchemaSync(): void
    {
        $filter = $this->getSchemaAssetsFilter();

        self::assertFalse($filter('ibexa_content'));
    }

    public function testProjectSchemaFilterStillApplies(): void
    {
        $filter = $this->getSchemaAssetsFilter();

        // ORM-managed, so ManagedTablesSchemaAssetFilter alone would allow it. It is rejected
        // only if the project's schema_filter survived alongside it.
        self::assertFalse($filter(SchemaAssetsFilterTestKernel::FILTERED_OUT_TABLE));
    }

    private function getSchemaAssetsFilter(): callable
    {
        $configuration = self::getServiceByClassName(
            Configuration::class,
            SchemaAssetsFilterTestKernel::DBAL_CONFIGURATION_SERVICE_ID
        );

        $filter = $configuration->getSchemaAssetsFilter();
        self::assertNotNull($filter);

        return $filter;
    }
}
