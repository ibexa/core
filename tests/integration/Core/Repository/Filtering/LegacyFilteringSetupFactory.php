<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Integration\Core\Repository\Filtering;

use Ibexa\Bundle\Core\DependencyInjection\ServiceTags;
use Ibexa\Contracts\Core\Repository\Values\Filter\CriterionQueryBuilder as FilteringCriterionQueryBuilder;
use Ibexa\Contracts\Core\Repository\Values\Filter\SortClauseQueryBuilder as FilteringSortClauseQueryBuilder;
use Ibexa\Contracts\Core\Test\Repository\SetupFactory\Legacy;
use Ibexa\Core\Base\Container\Compiler;
use Ibexa\Core\Base\ServiceContainer;
use Ibexa\Tests\Integration\Core\LegacyTestContainerBuilder;
use Ibexa\Tests\Integration\Core\Repository\Filtering\Fixtures\LegacyLocationSortQueryBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

final class LegacyFilteringSetupFactory extends Legacy
{
    public function getServiceContainer(): ServiceContainer
    {
        if (self::$serviceContainer instanceof ServiceContainer) {
            return self::$serviceContainer;
        }

        $installDir = self::getInstallationDir();

        $containerBuilder = new LegacyTestContainerBuilder();
        $loader = $containerBuilder->getCoreLoader();
        $loader->load('search_engines/legacy.yml');
        $loader->load('integration_legacy.yml');

        $this->externalBuildContainer($containerBuilder);

        $containerBuilder->setParameter(
            'ibexa.persistence.legacy.dsn',
            self::$dsn
        );

        $storageParam = $containerBuilder->hasParameter('ibexa.io.dir.storage')
            ? $containerBuilder->getParameter('ibexa.io.dir.storage')
            : null;
        $storageDir = is_string($storageParam) ? $storageParam : 'storage';
        $containerBuilder->setParameter(
            'ibexa.io.dir.root',
            self::$ioRootDir . '/' . $storageDir
        );

        $containerBuilder->addCompilerPass(new Compiler\Search\FieldRegistryPass());
        $containerBuilder->registerForAutoconfiguration(FilteringCriterionQueryBuilder::class)
            ->addTag(ServiceTags::FILTERING_CRITERION_QUERY_BUILDER);
        $containerBuilder->registerForAutoconfiguration(FilteringSortClauseQueryBuilder::class)
            ->addTag(ServiceTags::FILTERING_SORT_CLAUSE_QUERY_BUILDER);

        $loader->load('override.yml');

        self::$serviceContainer = new ServiceContainer(
            $containerBuilder,
            $installDir,
            self::getCacheDir(),
            true,
            true
        );

        return self::$serviceContainer;
    }

    protected function externalBuildContainer(ContainerBuilder $containerBuilder): void
    {
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
