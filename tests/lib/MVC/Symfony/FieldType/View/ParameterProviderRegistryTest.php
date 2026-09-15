<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Core\MVC\Symfony\FieldType\View;

use Ibexa\Core\MVC\Symfony\FieldType\View\ParameterProviderInterface;
use Ibexa\Core\MVC\Symfony\FieldType\View\ParameterProviderRegistry;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\TestCase;

#[CoversMethod(ParameterProviderRegistry::class, 'setParameterProvider')]
class ParameterProviderRegistryTest extends TestCase
{
    public function testSetHasParameterProvider()
    {
        $registry = new ParameterProviderRegistry();
        self::assertFalse($registry->hasParameterProvider('foo'));
        $registry->setParameterProvider(
            self::createStub(ParameterProviderInterface::class),
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
        $provider = self::createStub(ParameterProviderInterface::class);
        $registry = new ParameterProviderRegistry();
        $registry->setParameterProvider($provider, 'foo');
        self::assertSame($provider, $registry->getParameterProvider('foo'));
    }
}
