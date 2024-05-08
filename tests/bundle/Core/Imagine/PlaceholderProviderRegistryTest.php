<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Bundle\Core\Imagine;

use Ibexa\Bundle\Core\Imagine\PlaceholderProvider;
use Ibexa\Bundle\Core\Imagine\PlaceholderProviderRegistry;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Ibexa\Bundle\Core\Imagine\PlaceholderProviderRegistry
 */
class PlaceholderProviderRegistryTest extends TestCase
{
    private const FOO = 'foo';
    private const BAR = 'bar';

    /**
     * @depends      testGetProviderKnown
     */
    public function testConstructor()
    {
        $providers = [
            self::FOO => $this->getPlaceholderProviderMock(),
            self::BAR => $this->getPlaceholderProviderMock(),
        ];

        $registry = new PlaceholderProviderRegistry($providers);

        self::assertSame($providers[self::FOO], $registry->getProvider(self::FOO));
        self::assertSame($providers[self::BAR], $registry->getProvider(self::BAR));
    }

    /**
     * @depends      testGetProviderKnown
     */
    public function testAddProvider(): void
    {
        $provider = $this->getPlaceholderProviderMock();

        $registry = new PlaceholderProviderRegistry();
        $registry->addProvider(self::FOO, $provider);

        self::assertSame($provider, $registry->getProvider(self::FOO));
    }

    public function testSupports()
    {
        $registry = new PlaceholderProviderRegistry([
            'supported' => $this->getPlaceholderProviderMock(),
        ]);

        self::assertTrue($registry->supports('supported'));
        self::assertFalse($registry->supports('unsupported'));
    }

    public function testGetProviderKnown()
    {
        $provider = $this->getPlaceholderProviderMock();

        $registry = new PlaceholderProviderRegistry([
            self::FOO => $provider,
        ]);

        self::assertEquals($provider, $registry->getProvider(self::FOO));
    }

    public function testGetProviderUnknown()
    {
        $this->expectException(\InvalidArgumentException::class);

        $registry = new PlaceholderProviderRegistry([
            self::FOO => $this->getPlaceholderProviderMock(),
        ]);

        $registry->getProvider(self::BAR);
    }

    private function getPlaceholderProviderMock(): PlaceholderProvider
    {
        return $this->createMock(PlaceholderProvider::class);
    }
}
