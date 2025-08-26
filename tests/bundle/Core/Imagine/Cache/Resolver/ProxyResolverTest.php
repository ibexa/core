<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Bundle\Core\Imagine\Cache\Resolver;

use Ibexa\Bundle\Core\Imagine\Cache\Resolver\ProxyResolver;
use Liip\ImagineBundle\Imagine\Cache\Resolver\ResolverInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Ibexa\Bundle\Core\Imagine\Cache\Resolver\ProxyResolver
 */
final class ProxyResolverTest extends TestCase
{
    private ResolverInterface & MockObject $resolver;

    private string $path;

    private string $filter;

    protected function setUp(): void
    {
        parent::setUp();
        $this->resolver = $this->getMockBuilder(ResolverInterface::class)->getMock();
        $this->path = '7/4/2/0/247-1-eng-GB/img_0885.jpg';
        $this->filter = 'medium';
    }

    /**
     * @dataProvider getDataForTestResolve
     *
     * @param string[] $hosts
     */
    public function testResolve(array $hosts, string $resolvedPath, string $expected): void
    {
        $proxyResolver = new ProxyResolver($this->resolver, $hosts);

        $this->resolver
            ->expects(self::once())
            ->method('resolve')
            ->with($this->path, $this->filter)
            ->willReturn($resolvedPath);

        self::assertEquals($expected, $proxyResolver->resolve($this->path, $this->filter));
    }

    /**
     * @return iterable<string, array{0: string[], string, string}>
     */
    public static function getDataForTestResolve(): iterable
    {
        yield 'proxy host with trailing slash' => [
            ['https://qntmgroup.com/'],
            'https://ibexa.co/var/site/storage/images/_aliases/medium/7/4/2/0/247-1-eng-GB/img_0885.jpg',
            'https://qntmgroup.com/var/site/storage/images/_aliases/medium/7/4/2/0/247-1-eng-GB/img_0885.jpg',
        ];

        yield 'remove port using proxy host' => [
            ['https://ibexa.co'],
            'https://ibexa.co:8060/var/site/storage/images/_aliases/medium/7/4/2/0/247-1-eng-GB/img_0885.jpg',
            'https://ibexa.co/var/site/storage/images/_aliases/medium/7/4/2/0/247-1-eng-GB/img_0885.jpg',
        ];

        yield 'remove port using proxy host with trailing slash' => [
            ['https://ibexa.co'],
            'https://qntmgroup.com:8080/var/site/storage/images/_aliases/medium/7/4/2/0/247-1-eng-GB/img_0885.jpg',
            'https://ibexa.co/var/site/storage/images/_aliases/medium/7/4/2/0/247-1-eng-GB/img_0885.jpg',
        ];
    }
}
