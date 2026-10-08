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
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;

class ResolverFactoryTest extends TestCase
{
    /** @var MockObject|ConfigResolverInterface */
    private $configResolver;

    /** @var Stub|ResolverInterface */
    private $resolver;

    /** @var ResolverFactory */
    private $factory;

    protected function setUp(): void
    {
        parent::setUp();
        $this->configResolver = $this->getMockBuilder(ConfigResolverInterface::class)->getMock();
        $this->resolver = $this->createStub(ResolverInterface::class);
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
            ->expects($this->once())
            ->method('hasParameter')
            ->with('image_host')
            ->willReturn(true);

        $host = 'http://ibexa.co';

        $this->configResolver
            ->expects($this->once())
            ->method('getParameter')
            ->with('image_host')
            ->willReturn($host);

        $expected = new ProxyResolver($this->resolver, [$host]);

        $this->assertEquals($expected, $this->factory->createCacheResolver());
    }

    public function testCreateRelativeCacheResolver()
    {
        $this->configResolver
            ->expects($this->once())
            ->method('hasParameter')
            ->with('image_host')
            ->willReturn(true);

        $host = '/';

        $this->configResolver
            ->expects($this->once())
            ->method('getParameter')
            ->with('image_host')
            ->willReturn($host);

        $expected = new RelativeResolver($this->resolver);

        $this->assertEquals($expected, $this->factory->createCacheResolver());
    }
}

class_alias(ResolverFactoryTest::class, 'eZ\Bundle\EzPublishCoreBundle\Tests\Imagine\Cache\ResolverFactoryTest');
