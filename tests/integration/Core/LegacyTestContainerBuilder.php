<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Integration\Core;

use Ibexa\Bundle\Core\DependencyInjection\Compiler\ConsoleCommandPass;
use Ibexa\Bundle\Core\SiteAccess\Config\ComplexConfigProcessor;
use Ibexa\Contracts\Core\SiteAccess\ConfigProcessor;
use Ibexa\Contracts\Core\SiteAccess\ConfigResolverInterface;
use Ibexa\Core\Base\Container\Compiler;
use Ibexa\Core\MVC\Symfony\SiteAccess\SiteAccessService;
use Ibexa\Tests\Integration\Core\Repository\Container\Compiler\SetAllServicesPublicPass;
use Symfony\Component\Cache\Adapter\RedisAdapter;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\Config\Resource\FileResource;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\DependencyInjection\Reference;

final class LegacyTestContainerBuilder extends ContainerBuilder
{
    private ?LoaderInterface $coreLoader = null;

    public function __construct(?ParameterBagInterface $parameterBag = null)
    {
        parent::__construct($parameterBag);

        $this->initCoreTestContainerBuilder();
    }

    /**
     * @throws \Exception
     */
    private function initCoreTestContainerBuilder(): void
    {
        $this->addResource(new FileResource(__FILE__));

        $installDir = __DIR__ . '/../../..';
        $settingsPath = $installDir . '/src/lib/Resources/settings/';

        $this->coreLoader = $this->loadCoreSettings($settingsPath);

        // Cache settings (takes same env variables as ezplatform does, only supports "singleredis" setup)
        if (getenv('CUSTOM_CACHE_POOL') === 'singleredis') {
            $this->configureRedis();
        }

        $this->setParameter('ibexa.kernel.root_dir', $installDir);

        $this->registerCompilerPasses();

        $this->registerCoreBundleServices();
    }

    /**
     * @throws \Exception
     */
    private function loadCoreSettings(string $settingsPath): LoaderInterface
    {
        $loader = new YamlFileLoader(
            $this,
            new FileLocator([$settingsPath, __DIR__ . '/Resources/settings'])
        );

        $loader->load('fieldtype_constraints_storage.yaml');
        $loader->load('fieldtype_external_storages.yml');
        $loader->load('fieldtype_services.yml');
        $loader->load('fieldtypes.yml');
        $loader->load('indexable_fieldtypes.yml');
        $loader->load('io.yml');
        $loader->load('repository.yml');
        $loader->load('repository/inner.yml');
        $loader->load('repository/event.yml');
        $loader->load('repository/siteaccessaware.yml');
        $loader->load('repository/autowire.yml');
        $loader->load('roles.yml');
        $loader->load('storage_engines/common.yml');
        $loader->load('storage_engines/cache.yml');
        $loader->load('storage_engines/legacy.yml');
        $loader->load('storage_engines/shortcuts.yml');
        $loader->load('settings.yml');
        $loader->load('utils.yml');
        $loader->load('policies.yml');
        $loader->load('events.yml');
        $loader->load('thumbnails.yml');
        $loader->load('tokens.yml');
        $loader->load('content_location_mapper.yml');

        // tests/integration/Core/Resources/settings/common.yml
        $loader->load('common.yml');

        return $loader;
    }

    private function configureRedis(): void
    {
        $this
            ->register('ezpublish.cache_pool.driver.redis', 'Redis')
            ->addMethodCall('connect', [(getenv('CACHE_HOST') ?: '127.0.0.1'), 6379, 2.5]);

        $this
            ->register('ibexa.cache_pool.driver', RedisAdapter::class)
            ->setArguments([new Reference('ezpublish.cache_pool.driver.redis'), '', 120]);
    }

    private function registerCompilerPasses(): void
    {
        $this->addCompilerPass(new Compiler\FieldTypeRegistryPass(), PassConfig::TYPE_OPTIMIZE);
        $this->addCompilerPass(
            new Compiler\Persistence\FieldTypeRegistryPass(),
            PassConfig::TYPE_OPTIMIZE
        );

        $this->addCompilerPass(new Compiler\Storage\ExternalStorageRegistryPass());
        $this->addCompilerPass(new Compiler\Storage\Legacy\FieldValueConverterRegistryPass());
        $this->addCompilerPass(new Compiler\Storage\Legacy\RoleLimitationConverterPass());

        $this->addCompilerPass(new Compiler\Search\Legacy\CriterionFieldValueHandlerRegistryPass());
        $this->addCompilerPass(new Compiler\Search\Legacy\SortClauseConverterPass());

        $this->addCompilerPass(new ConsoleCommandPass());

        // Symfony 4 makes services private by default. Test cases are not prepared for this.
        // This is a simple workaround to override services as public.
        $this->addCompilerPass(new SetAllServicesPublicPass());
    }

    /**
     * @return \Symfony\Component\Config\Loader\LoaderInterface
     */
    public function getCoreLoader(): LoaderInterface
    {
        if (null === $this->coreLoader) {
            throw new \RuntimeException('Core loader is not initialized');
        }

        return $this->coreLoader;
    }

    /**
     * Register needed services from Core Bundle.
     *
     * Note: if a service from a bundle layer is required to be registered here, it most likely
     * means that the service should be located in core lib layer in the first place.
     */
    private function registerCoreBundleServices(): void
    {
        $this->registerSiteAccessConfigProcessingServices();
    }

    private function registerSiteAccessConfigProcessingServices(): void
    {
        if ($this->hasDefinition(ConfigProcessor::class)) {
            return;
        }

        $definition = new Definition(ComplexConfigProcessor::class);
        $definition->setArgument('$configResolver', new Reference(ConfigResolverInterface::class));
        $definition->setArgument('$siteAccessService', new Reference(SiteAccessService::class));

        $this->setDefinition(ConfigProcessor::class, $definition);
    }
}
