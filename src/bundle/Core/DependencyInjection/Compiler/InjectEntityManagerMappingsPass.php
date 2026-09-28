<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Bundle\Core\DependencyInjection\Compiler;

use Doctrine\ORM\Mapping\Driver\AttributeDriver;
use Doctrine\ORM\Mapping\Driver\SimplifiedXmlDriver;
use Ibexa\Bundle\Core\Doctrine\ManagedTablesSchemaAssetFilter;
use LogicException;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Creating Entity Mapping drivers is based on AbstractDoctrineExtension from doctrine/doctrine-bundle.
 * It's required to keep following logic updated with Doctrine changes.
 */
final class InjectEntityManagerMappingsPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        $entityManagers = $container->getParameter('doctrine.entity_managers');
        $entityMappings = $container->getParameter('ibexa.orm.entity_mappings');

        $mappingDriverConfig = $this->prepareMappingDriverConfig($entityMappings, $container);

        foreach ($entityManagers as $entityManagerName => $serviceName) {
            if (!str_starts_with($entityManagerName, 'ibexa_')) {
                continue;
            }

            $chainMetadataDriverDefinition = $container->getDefinition(
                sprintf('doctrine.orm.%s_metadata_driver', $entityManagerName)
            );

            $this->protectLegacySchemaFromOrmSchemaSync($entityManagerName, $container);

            foreach ($mappingDriverConfig as $driverType => $driverPaths) {
                $metadataDriverServiceName = "doctrine.orm.{$entityManagerName}_{$driverType}_metadata_driver";
                $metadataDriverDefinition = $this->createMetadataDriverDefinition($driverType, $driverPaths);

                $class = $metadataDriverDefinition->getClass();
                if (null !== $class && (str_contains($class, 'yml') || str_contains($class, 'xml'))) {
                    $metadataDriverDefinition->setArguments([array_flip($driverPaths)]);
                    $metadataDriverDefinition->addMethodCall('setGlobalBasename', ['mapping']);
                }

                $container->setDefinition($metadataDriverServiceName, $metadataDriverDefinition);

                foreach ($driverPaths as $prefix => $driverPath) {
                    $chainMetadataDriverDefinition->addMethodCall(
                        'addDriver',
                        [new Reference($metadataDriverServiceName), $prefix]
                    );
                }
            }
        }
    }

    /**
     * Protects Ibexa's legacy (non-Doctrine-ORM) schema, sharing the same
     * connection as this entity manager, from being dropped by
     * doctrine:schema:update. ORM 3 always behaves as if --complete was
     * passed, dropping any table not backed by a registered ORM entity.
     *
     * The filter is registered through DoctrineBundle's
     * "doctrine.dbal.schema_filter" tag rather than by calling
     * Configuration::setSchemaAssetsFilter() directly, because that setter is
     * not additive: DoctrineBundle's own DbalSchemaFilterPass calls it as well,
     * to install a SchemaAssetsFilterManager wrapping every tagged filter, so
     * whichever pass ran last would silently discard the other one's filter -
     * either dropping a project's own "doctrine.dbal.<connection>.schema_filter"
     * configuration, or dropping the legacy schema protection. Going through
     * the tag makes both apply: SchemaAssetsFilterManager rejects an asset as
     * soon as any single filter rejects it.
     *
     * This requires the pass to run before DbalSchemaFilterPass, which is why
     * IbexaCoreBundle registers it with a higher priority.
     */
    private function protectLegacySchemaFromOrmSchemaSync(string $entityManagerName, ContainerBuilder $container): void
    {
        if (!str_starts_with($entityManagerName, 'ibexa_')) {
            return;
        }

        $connection = substr($entityManagerName, strlen('ibexa_'));
        $configurationId = sprintf('doctrine.dbal.%s_connection.configuration', $connection);

        if (!$container->hasDefinition($configurationId)) {
            return;
        }

        $container->findDefinition(ManagedTablesSchemaAssetFilter::class)->addTag(
            'doctrine.dbal.schema_filter',
            ['connection' => $connection]
        );
    }

    private function createMetadataDriverDefinition($driverType, $driverPaths): Definition
    {
        $metadataDriver = new Definition($this->getMetadataDriverClass($driverType));
        $arguments = [array_values($driverPaths)];

        $metadataDriver->setArguments($arguments);
        $metadataDriver->setPublic(false);

        return $metadataDriver;
    }

    /**
     * Maps a mapping driver type to its class, mirroring
     * DoctrineExtension::getMetadataDriverClass() (limited to the driver types Ibexa's own
     * "entity_mappings" configuration allows). DoctrineBundle 3 stopped exposing this mapping
     * as "doctrine.orm.metadata.<type>.class" container parameters, so it can no longer be
     * resolved through a parameter placeholder and is hardcoded here instead.
     */
    private function getMetadataDriverClass(string $driverType): string
    {
        return match ($driverType) {
            'attribute' => AttributeDriver::class,
            'xml' => SimplifiedXmlDriver::class,
            default => throw new LogicException(sprintf('Unknown "%s" metadata driver type.', $driverType)),
        };
    }

    private function prepareMappingDriverConfig(array $entityManagerConfig, ContainerBuilder $container): array
    {
        $bundles = $container->getParameter('kernel.bundles');
        $driverConfig = [];
        foreach ($entityManagerConfig as $mappingName => $config) {
            $config = array_replace([
                'dir' => false,
                'type' => false,
                'prefix' => false,
            ], (array) $config);

            $config['dir'] = $container->getParameterBag()->resolveValue($config['dir']);

            if ($config['is_bundle']) {
                $bundle = null;
                foreach ($bundles as $bundleName => $class) {
                    if ($mappingName === $bundleName) {
                        $bundle = new \ReflectionClass($class);

                        break;
                    }
                }

                if (null === $bundle) {
                    throw new \InvalidArgumentException(
                        sprintf(
                            'Bundle "%s" does not exist or it is not enabled.',
                            $mappingName
                        )
                    );
                }

                $config = $this->getMappingDriverBundleConfigDefaults($config, $bundle);
            }

            if (!is_dir($config['dir'])) {
                throw new \InvalidArgumentException(sprintf(
                    'Invalid Doctrine mapping path given. Cannot load Doctrine mapping/bundle named "%s".',
                    $mappingName
                ));
            }

            $driverConfig[$config['type']][$config['prefix']] = realpath($config['dir']) ?: $config['dir'];
        }

        return $driverConfig;
    }

    private function getMappingDriverBundleConfigDefaults(
        array $bundleConfig,
        \ReflectionClass $bundle
    ): array {
        $bundleDir = \dirname($bundle->getFileName());

        if (!$bundleConfig['type'] || !$bundleConfig['dir'] || !$bundleConfig['prefix']) {
            throw new \InvalidArgumentException(
                "Entity Mapping has invalid configuration. Please provide 'type', 'dir' and 'prefix' parameters."
            );
        }

        $bundleConfig['dir'] = $bundleDir . '/' . $bundleConfig['dir'];

        return $bundleConfig;
    }
}
