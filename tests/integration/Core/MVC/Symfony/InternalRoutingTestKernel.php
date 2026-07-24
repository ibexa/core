<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Integration\Core\MVC\Symfony;

use Ibexa\Contracts\Core\Test\IbexaTestKernel;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

final class InternalRoutingTestKernel extends IbexaTestKernel
{
    public function registerContainerConfiguration(LoaderInterface $loader): void
    {
        parent::registerContainerConfiguration($loader);

        $loader->load(static function (ContainerBuilder $container): void {
            self::loadRouting($container);
        });
    }

    private static function loadRouting(ContainerBuilder $container): void
    {
        $container->loadFromExtension('framework', [
            'router' => [
                'resource' => '@IbexaCoreBundle/Resources/config/routing/internal.yml',
            ],
        ]);
    }
}
