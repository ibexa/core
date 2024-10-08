<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Core\MVC\Symfony\View;

use ArrayIterator;
use Ibexa\Contracts\Core\MVC\View\VariableProvider;
use Ibexa\Core\Base\Exceptions\NotFoundException;
use Ibexa\Core\MVC\Symfony\View\GenericVariableProviderRegistry;
use Ibexa\Core\MVC\Symfony\View\View;
use PHPUnit\Framework\TestCase;

final class VariableProviderRegistryTest extends TestCase
{
    private function getRegistry(array $providers): GenericVariableProviderRegistry
    {
        return new GenericVariableProviderRegistry(
            new ArrayIterator($providers)
        );
    }

    private function getProvider(string $identifier): VariableProvider
    {
        return new class($identifier) implements VariableProvider {
            private $identifier;

            public function __construct(string $identifier)
            {
                $this->identifier = $identifier;
            }

            public function getIdentifier(): string
            {
                return $this->identifier;
            }

            public function getTwigVariables(View $view, array $options = []): object
            {
                return (object)[
                    $this->identifier . '_parameter' => $this->identifier . '_value',
                ];
            }
        };
    }

    public function testParameterProviderGetter(): void
    {
        $registry = $this->getRegistry([
            $this->getProvider('provider_a'),
            $this->getProvider('provider_b'),
        ]);

        $providerA = $registry->getTwigVariableProvider('provider_a');
        $providerB = $registry->getTwigVariableProvider('provider_b');

        self::assertEquals($providerA->getIdentifier(), 'provider_a');
        self::assertEquals($providerB->getIdentifier(), 'provider_b');
    }

    public function testParameterNotFoundProviderGetter(): void
    {
        $this->expectException(NotFoundException::class);

        $registry = $this->getRegistry([
            $this->getProvider('provider_a'),
            $this->getProvider('provider_b'),
        ]);

        $registry->getTwigVariableProvider('provider_c');
    }

    public function testParameterProviderSetter(): void
    {
        $registry = $this->getRegistry([
            $this->getProvider('provider_a'),
            $this->getProvider('provider_b'),
        ]);

        $hasProviderC = $registry->hasTwigVariableProvider('provider_c');

        self::assertFalse($hasProviderC);

        $registry->setTwigVariableProvider($this->getProvider('provider_c'));

        $providerC = $registry->getTwigVariableProvider('provider_c');
        self::assertEquals($providerC->getIdentifier(), 'provider_c');
    }

    public function testParameterProviderChecker(): void
    {
        $registry = $this->getRegistry([
            $this->getProvider('provider_a'),
            $this->getProvider('provider_b'),
        ]);

        self::assertTrue($registry->hasTwigVariableProvider('provider_a'));
        self::assertTrue($registry->hasTwigVariableProvider('provider_b'));
        self::assertFalse($registry->hasTwigVariableProvider('provider_c'));
    }
}
