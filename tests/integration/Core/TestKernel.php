<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Integration\Core;

use DAMA\DoctrineTestBundle\DAMADoctrineTestBundle;
use Ibexa\Bundle\Test\Core\IbexaTestCoreBundle;
use Ibexa\Contracts\Core\Persistence\Handler;
use Ibexa\Contracts\Core\Repository\BookmarkService;
use Ibexa\Contracts\Core\Repository\TrashService;
use Ibexa\Contracts\Core\Test\Persistence\Fixture\YamlFixture;
use Ibexa\Contracts\Test\Core\IbexaTestKernel as BaseIbexaTestKernel;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Loads the same fixture set {@see \Ibexa\Contracts\Core\Test\IbexaTestKernel} (the package-internal
 * kernel predating ibexa/test-core) already used, instead of the shared kernel's own generic default,
 * so tests already calibrated against it keep passing unchanged.
 *
 * Registers DAMADoctrineTestBundle so mutating tests can run without reimporting schema/fixtures
 * before each one: DAMA wraps each test in a transaction that's rolled back afterwards, layered on
 * top of the one-time schema/fixture import tests/integration/bootstrap.php already does.
 */
final class TestKernel extends BaseIbexaTestKernel
{
    public function registerContainerConfiguration(LoaderInterface $loader): void
    {
        $loader->load(static function (ContainerBuilder $container): void {
            $container->setParameter('ibexa.kernel.root_dir', dirname(__DIR__, 3));
        });

        parent::registerContainerConfiguration($loader);
    }

    public function registerBundles(): iterable
    {
        yield from parent::registerBundles();

        yield new IbexaTestCoreBundle();
        yield new DAMADoctrineTestBundle();
    }

    protected static function getExposedServicesByClass(): iterable
    {
        yield from parent::getExposedServicesByClass();

        yield TrashService::class;
        yield BookmarkService::class;
        yield Handler::class;
    }

    public function getFixtures(): iterable
    {
        yield new YamlFixture(__DIR__ . '/Repository/_fixtures/Legacy/data/test_data.yaml');
    }
}
