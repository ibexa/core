<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Bundle\Core\ApiLoader;

use Ibexa\Bundle\Core\ApiLoader\CacheFactory;
use Ibexa\Contracts\Core\SiteAccess\ConfigResolverInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\AdapterInterface;
use Symfony\Component\Cache\Adapter\TagAwareAdapter;
use Symfony\Component\DependencyInjection\ContainerInterface;

class CacheFactoryTest extends TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject */
    private $configResolver;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    private $container;

    protected function setUp(): void
    {
        parent::setUp();
        $this->configResolver = $this->createMock(ConfigResolverInterface::class);
        $this->container = $this->createMock(ContainerInterface::class);
    }

    /**
     * @return array
     */
    public function providerGetService()
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
    public function testGetService($name, $expected)
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

        $factory = new CacheFactory();
        $factory->setContainer($this->container);

        self::assertInstanceOf(TagAwareAdapter::class, $factory->getCachePool($this->configResolver));
    }
}
