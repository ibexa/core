<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Integration\Core\Repository\Filtering;

use Ibexa\Contracts\Core\Test\Repository\SetupFactory\Legacy;
use Ibexa\Core\Base\ServiceContainer;
use Ibexa\Bundle\Core\DependencyInjection\ServiceTags;
use Ibexa\Tests\Integration\Core\Repository\Filtering\Fixtures\LegacyLocationSortQueryBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\DependencyInjection\ContainerBuilder;

final class LegacyFilteringSetupFactory extends Legacy
{
    public function getServiceContainer()
    {
        // Force rebuilding the container to include test-only services.
        self::$serviceContainer = null;

        return parent::getServiceContainer();
    }

    protected function externalBuildContainer(ContainerBuilder $containerBuilder)
    {
        parent::externalBuildContainer($containerBuilder);

        $loader = new YamlFileLoader(
            $containerBuilder,
            new FileLocator(__DIR__ . '/Resources/config')
        );
        $loader->load('services/legacy_sort_clause.yaml');

        if ($containerBuilder->hasDefinition(LegacyLocationSortQueryBuilder::class)) {
            $containerBuilder
                ->getDefinition(LegacyLocationSortQueryBuilder::class)
                ->addTag(ServiceTags::FILTERING_SORT_CLAUSE_QUERY_BUILDER);
        } else {
            $containerBuilder
                ->register(LegacyLocationSortQueryBuilder::class, LegacyLocationSortQueryBuilder::class)
                ->addTag(ServiceTags::FILTERING_SORT_CLAUSE_QUERY_BUILDER);
        }
    }
}
