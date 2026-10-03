<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\SchemaParity\Core;

use Doctrine\Bundle\MigrationsBundle\DoctrineMigrationsBundle;
use Ibexa\Bundle\DoctrineMigrations\IbexaDoctrineMigrationsBundle;
use Ibexa\Bundle\Test\Core\IbexaTestCoreBundle;
use Ibexa\Contracts\Test\Core\IbexaTestKernel;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Core's bundles plus the Doctrine Migrations path. The integration suites' kernels don't register
 * the migrations bundles, and they bring fixtures this test doesn't need.
 */
final class SchemaParityTestKernel extends IbexaTestKernel
{
    public function registerContainerConfiguration(LoaderInterface $loader): void
    {
        $loader->load(static function (ContainerBuilder $container): void {
            $container->setParameter('ibexa.kernel.root_dir', dirname(__DIR__, 2));
        });

        parent::registerContainerConfiguration($loader);
    }

    public function registerBundles(): iterable
    {
        yield from parent::registerBundles();

        yield new IbexaTestCoreBundle();
        yield new DoctrineMigrationsBundle();
        yield new IbexaDoctrineMigrationsBundle();
    }
}
