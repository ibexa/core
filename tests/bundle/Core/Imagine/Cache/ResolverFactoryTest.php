<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Bundle\Core\Imagine\Cache;

use Ibexa\Bundle\Core\Imagine\Cache\Resolver\RelativeResolver;
use Ibexa\Bundle\Core\Imagine\Cache\ResolverFactory;
use Ibexa\Contracts\Core\SiteAccess\ConfigResolverInterface;
use Liip\ImagineBundle\Imagine\Cache\Resolver\ProxyResolver;
use Liip\ImagineBundle\Imagine\Cache\Resolver\ResolverInterface;
use PHPUnit\Framework\TestCase;

class ResolverFactoryTest extends TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject|\Ibexa\Contracts\Core\SiteAccess\ConfigResolverInterface */
    private $configResolver;

    /** @var \PHPUnit\Framework\MockObject\MockObject|\Liip\ImagineBundle\Imagine\Cache\Resolver\ResolverInterface */
    private $resolver;

    /** @var \Ibexa\Bundle\Core\Imagine\Cache\ResolverFactory */
    private $factory;

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

    public function testCreateProxyCacheResolver()
    {
        $this->configResolver
            ->expects(self::at(0))
            ->method('hasParameter')
            ->with('image_host')
            ->willReturn(true);

        $host = 'http://ibexa.co';

        $this->configResolver
            ->expects(self::at(1))
            ->method('getParameter')
            ->with('image_host')
            ->willReturn($host);

        $expected = new ProxyResolver($this->resolver, [$host]);

        self::assertEquals($expected, $this->factory->createCacheResolver());
    }

    public function testCreateRelativeCacheResolver()
    {
        $this->configResolver
            ->expects(self::at(0))
            ->method('hasParameter')
            ->with('image_host')
            ->willReturn(true);

        $host = '/';

        $this->configResolver
            ->expects(self::at(1))
            ->method('getParameter')
            ->with('image_host')
            ->willReturn($host);

        $expected = new RelativeResolver($this->resolver);

        self::assertEquals($expected, $this->factory->createCacheResolver());
    }
}
