<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Bundle\Core\ApiLoader;

use Ibexa\Bundle\Core\ApiLoader\CacheFactory;
use Ibexa\Contracts\Core\SiteAccess\ConfigResolverInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\AdapterInterface;
use Symfony\Component\Cache\Adapter\TagAwareAdapter;
use Symfony\Component\DependencyInjection\ContainerInterface;

final class CacheFactoryTest extends TestCase
{
    private ConfigResolverInterface&MockObject $configResolver;

    private ContainerInterface&MockObject $container;

    protected function setUp(): void
    {
        parent::setUp();
        $this->configResolver = $this->createMock(ConfigResolverInterface::class);
        $this->container = $this->createMock(ContainerInterface::class);
    }

    /**
     * @return array<array{string, string}>
     */
    public function providerGetService(): array
    {
        return [
            ['default', 'default'],
            ['ez_site1', 'ez_site1'],
            ['xyZ', 'xyZ'],
        ];
    }

    /**
     * @dataProvider providerGetService
     */
    public function testGetService($name, $expected): void
    {
        $this->configResolver
            ->expects(self::once())
            ->method('getParameter')
            ->with('cache_service_name')
            ->will(self::returnValue($name));

        $this->container
            ->expects(self::once())
            ->method('get')
            ->with($expected)
            ->will(self::returnValue($this->createMock(AdapterInterface::class)));

        $factory = new CacheFactory($this->container);

        self::assertInstanceOf(TagAwareAdapter::class, $factory->getCachePool($this->configResolver));
    }
}
