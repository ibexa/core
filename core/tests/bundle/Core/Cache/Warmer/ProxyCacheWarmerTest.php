<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Bundle\Core\Cache\Warmer;

use Ibexa\Bundle\Core\Cache\Warmer\ProxyCacheWarmer;
use Ibexa\Core\Repository\ProxyFactory\ProxyGeneratorInterface;
use PHPUnit\Framework\TestCase;

final class ProxyCacheWarmerTest extends TestCase
{
    /** @var \Ibexa\Core\Repository\ProxyFactory\ProxyGeneratorInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $proxyGenerator;

    /** @var \Ibexa\Bundle\Core\Cache\Warmer\ProxyCacheWarmer */
    private $proxyCacheWarmer;

    protected function setUp(): void
    {
        $this->proxyGenerator = $this->createMock(ProxyGeneratorInterface::class);
        $this->proxyCacheWarmer = new ProxyCacheWarmer($this->proxyGenerator);
    }

    public function testIsOptional(): void
    {
        self::assertFalse($this->proxyCacheWarmer->isOptional());
    }

    public function testWarmUp(): void
    {
        $this->proxyGenerator
            ->expects(self::once())
            ->method('warmUp')
            ->with(ProxyCacheWarmer::PROXY_CLASSES);

        $this->proxyCacheWarmer->warmUp('/cache/dir');
    }
}
