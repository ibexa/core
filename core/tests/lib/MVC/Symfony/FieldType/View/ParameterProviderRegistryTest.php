<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Core\MVC\Symfony\FieldType\View;

use Ibexa\Core\MVC\Symfony\FieldType\View\ParameterProviderInterface;
use Ibexa\Core\MVC\Symfony\FieldType\View\ParameterProviderRegistry;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Ibexa\Core\MVC\Symfony\FieldType\View\ParameterProviderRegistry::setParameterProvider
 */
class ParameterProviderRegistryTest extends TestCase
{
    public function testSetHasParameterProvider()
    {
        $registry = new ParameterProviderRegistry();
        self::assertFalse($registry->hasParameterProvider('foo'));
        $registry->setParameterProvider(
            $this->createMock(ParameterProviderInterface::class),
            'foo'
        );
        self::assertTrue($registry->hasParameterProvider('foo'));
    }

    public function testGetParameterProviderFail()
    {
        $this->expectException(\InvalidArgumentException::class);

        $registry = new ParameterProviderRegistry();
        $registry->getParameterProvider('foo');
    }

    public function testGetParameterProvider()
    {
        $provider = $this->createMock(ParameterProviderInterface::class);
        $registry = new ParameterProviderRegistry();
        $registry->setParameterProvider($provider, 'foo');
        self::assertSame($provider, $registry->getParameterProvider('foo'));
    }
}
