<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Bundle\Core\Imagine\Cache\Resolver;

use Ibexa\Bundle\Core\Imagine\Cache\Resolver\RelativeResolver;
use Liip\ImagineBundle\Imagine\Cache\Resolver\ResolverInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Ibexa\Bundle\Core\Imagine\Cache\Resolver\RelativeResolver
 */
final class RelativeResolverTest extends TestCase
{
    private ResolverInterface & MockObject $liipResolver;

    protected function setUp(): void
    {
        parent::setUp();
        $this->liipResolver = $this->getMockBuilder(ResolverInterface::class)->getMock();
    }

    public function testResolve(): void
    {
        $resolver = new RelativeResolver($this->liipResolver);

        $path = '7/4/2/0/247-1-eng-GB/test.png';
        $filter = 'big';

        $absolute = 'https://ibexa.co/var/site/storage/images/_aliases/big/7/4/2/0/247-1-eng-GB/test.png';
        $expected = '/var/site/storage/images/_aliases/big/7/4/2/0/247-1-eng-GB/test.png';

        $this->liipResolver
            ->expects(self::once())
            ->method('resolve')
            ->with($path, $filter)
            ->willReturn($absolute);

        self::assertSame($expected, $resolver->resolve($path, $filter));
    }
}
