<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Bundle\Core\Imagine\Cache;

use Ibexa\Bundle\Core\Imagine\Cache\Resolver\RelativeResolver;
use Ibexa\Bundle\Core\Imagine\Cache\ResolverFactory;
use Ibexa\Contracts\Core\SiteAccess\ConfigResolverInterface;
use Liip\ImagineBundle\Imagine\Cache\Resolver\ProxyResolver;
use Liip\ImagineBundle\Imagine\Cache\Resolver\ResolverInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Ibexa\Bundle\Core\Imagine\Cache\ResolverFactory
 */
final class ResolverFactoryTest extends TestCase
{
    private ConfigResolverInterface&MockObject $configResolver;

    private ResolverInterface & MockObject $resolver;

    private ResolverFactory $factory;

    protected function setUp(): void
    {
        parent::setUp();
        $this->configResolver = $this->getMockBuilder(ConfigResolverInterface::class)->getMock();
        $this->resolver = $this->getMockBuilder(ResolverInterface::class)->getMock();
        $this->factory = new ResolverFactory(
            $this->configResolver,
            $this->resolver,
            ProxyResolver::class,
            RelativeResolver::class
        );
    }

    public function testCreateProxyCacheResolver(): void
    {
        $this->configResolver
            ->expects(self::once())
            ->method('hasParameter')
            ->with('image_host')
            ->willReturn(true);

        $host = 'https://ibexa.co';

        $this->configResolver
            ->expects(self::once())
            ->method('getParameter')
            ->with('image_host')
            ->willReturn($host);

        $expected = new ProxyResolver($this->resolver, [$host]);

        self::assertEquals($expected, $this->factory->createCacheResolver());
    }

    public function testCreateRelativeCacheResolver(): void
    {
        $this->configResolver
            ->expects(self::once())
            ->method('hasParameter')
            ->with('image_host')
            ->willReturn(true);

        $host = '/';

        $this->configResolver
            ->expects(self::once())
            ->method('getParameter')
            ->with('image_host')
            ->willReturn($host);

        $expected = new RelativeResolver($this->resolver);

        self::assertEquals($expected, $this->factory->createCacheResolver());
    }
}
