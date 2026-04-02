<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Bundle\Core;

use Ibexa\Bundle\Core\IbexaCoreBundle;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * @covers \Ibexa\Bundle\Core\IbexaCoreBundle
 */
final class IbexaCoreBundleTest extends TestCase
{
    private IbexaCoreBundle $bundle;

    protected function setUp(): void
    {
        $this->bundle = new IbexaCoreBundle();
    }

    protected function tearDown(): void
    {
        unset($_SERVER['PLATFORM_RELATIONSHIPS']);
    }

    public function testBuildDoesNotThrowWhenNotOnCloud(): void
    {
        unset($_SERVER['PLATFORM_RELATIONSHIPS']);

        $container = new ContainerBuilder();
        // No exception expected
        $this->bundle->build($container);

        $this->expectNotToPerformAssertions();
    }

    public function testBuildDoesNotThrowWhenOnCloudWithIbexaCloudBundle(): void
    {
        $_SERVER['PLATFORM_RELATIONSHIPS'] = 'some_value';

        $container = new ContainerBuilder();
        $container->setParameter('kernel.bundles', ['IbexaCloudBundle' => 'Ibexa\Bundle\Cloud\IbexaCloudBundle']);

        $this->bundle->build($container);

        $this->expectNotToPerformAssertions();
    }

    public function testBuildThrowsWhenOnCloudWithoutIbexaCloudBundle(): void
    {
        $_SERVER['PLATFORM_RELATIONSHIPS'] = 'some_value';

        $container = new ContainerBuilder();
        $container->setParameter('kernel.bundles', []);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('The package `ibexa/cloud` is mandatory for Ibexa Cloud deployments.');

        $this->bundle->build($container);
    }
}
