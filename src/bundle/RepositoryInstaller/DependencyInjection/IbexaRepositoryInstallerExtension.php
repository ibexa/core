<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
namespace Ibexa\Bundle\RepositoryInstaller\DependencyInjection;

use Ibexa\Contracts\Test\Core\Bootstrapper\HookInterface;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

class IbexaRepositoryInstallerExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container)
    {
        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.yml');

        if ($this->shouldRegisterBootstrapperHook($container)) {
            $loader->load('bootstrapper.yml');
        }
    }

    /**
     * "ibexa/test-core" is a dev dependency, so its HookInterface is absent from a production
     * install - and even where it is present, integration-test bootstrap services have no business
     * being built outside the "test" environment.
     */
    private function shouldRegisterBootstrapperHook(ContainerBuilder $container): bool
    {
        return 'test' === $container->getParameter('kernel.environment')
            && interface_exists(HookInterface::class);
    }
}

class_alias(IbexaRepositoryInstallerExtension::class, 'EzSystems\PlatformInstallerBundle\DependencyInjection\EzSystemsPlatformInstallerExtension');
