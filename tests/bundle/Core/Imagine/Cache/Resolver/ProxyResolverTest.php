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

final class ProxyResolverTest extends TestCase
{
    private const string RESOLVED_PATH_URI = 'https://ibexa.co/var/site/storage/images/_aliases/medium/7/4/2/0/247-1-eng-GB/img_0885.jpg';
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
     * @return iterable<string, array{string[], string, string}>
     */
    public static function getDataForTestResolveUsingProxyHost(): iterable
    {
        yield 'resolve handles trailing slash' => [
            ['https://ibexa.co/'],
            self::RESOLVED_PATH_URI,
            'https://ibexa.co/var/site/storage/images/_aliases/medium/7/4/2/0/247-1-eng-GB/img_0885.jpg',
        ];

        yield 'resolve removes port' => [
            ['https://ibexa.co'],
            'https://ibexa.co:8060/var/site/storage/images/_aliases/medium/7/4/2/0/247-1-eng-GB/img_0885.jpg',
            self::RESOLVED_PATH_URI,
        ];

        yield 'resolve removes port and handles trailing slash' => [
            ['https://ibexa.co/'],
            'https://ibexa.co:8080/var/site/storage/images/_aliases/medium/7/4/2/0/247-1-eng-GB/img_0885.jpg',
            self::RESOLVED_PATH_URI,
        ];
    }

    /**
     * @dataProvider getDataForTestResolveUsingProxyHost
     *
     * @param string[] $hosts
     */
    public function testResolveUsingProxyHost(array $hosts, string $resolvedPath, string $expectedUri): void
    {
        $proxyResolver = new ProxyResolver($this->resolver, $hosts);

        $this->resolver
            ->expects(self::once())
            ->method('resolve')
            ->with($this->path, $this->filter)
            ->willReturn($resolvedPath);

        self::assertEquals($expectedUri, $proxyResolver->resolve($this->path, $this->filter));
    }
}
