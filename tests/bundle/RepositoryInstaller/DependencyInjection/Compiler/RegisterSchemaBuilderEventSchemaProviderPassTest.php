<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Bundle\RepositoryInstaller\DependencyInjection\Compiler;

use Doctrine\Migrations\Provider\SchemaProvider;
use Ibexa\Bundle\RepositoryInstaller\DependencyInjection\Compiler\RegisterSchemaBuilderEventSchemaProviderPass;
use Ibexa\Bundle\RepositoryInstaller\Migration\SchemaBuilderEventSchemaProvider;
use Ibexa\Contracts\DoctrineMigrations\Migrations\IbexaOnlyDependencyFactory;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

/**
 * @covers \Ibexa\Bundle\RepositoryInstaller\DependencyInjection\Compiler\RegisterSchemaBuilderEventSchemaProviderPass
 */
final class RegisterSchemaBuilderEventSchemaProviderPassTest extends TestCase
{
    public function testWiresSchemaProviderOntoIbexaOnlyDependencyFactory(): void
    {
        $container = new ContainerBuilder();
        $container->setDefinition(IbexaOnlyDependencyFactory::SERVICE_ID, new Definition());
        $container->setDefinition(SchemaBuilderEventSchemaProvider::class, new Definition());

        (new RegisterSchemaBuilderEventSchemaProviderPass())->process($container);

        $calls = $container->getDefinition(IbexaOnlyDependencyFactory::SERVICE_ID)->getMethodCalls();
        self::assertCount(1, $calls);
        self::assertSame('setService', $calls[0][0]);
        self::assertSame(SchemaProvider::class, $calls[0][1][0]);
        self::assertInstanceOf(Reference::class, $calls[0][1][1]);
        self::assertSame(SchemaBuilderEventSchemaProvider::class, (string) $calls[0][1][1]);
    }

    public function testIsNoOpWhenIbexaOnlyDependencyFactoryIsNotDefined(): void
    {
        $container = new ContainerBuilder();
        $container->setDefinition(SchemaBuilderEventSchemaProvider::class, new Definition());

        (new RegisterSchemaBuilderEventSchemaProviderPass())->process($container);

        $this->addToAssertionCount(1);
    }

    public function testIsNoOpWhenSchemaBuilderEventSchemaProviderIsNotDefined(): void
    {
        $container = new ContainerBuilder();
        $container->setDefinition(IbexaOnlyDependencyFactory::SERVICE_ID, new Definition());

        (new RegisterSchemaBuilderEventSchemaProviderPass())->process($container);

        self::assertSame(
            [],
            $container->getDefinition(IbexaOnlyDependencyFactory::SERVICE_ID)->getMethodCalls()
        );
    }
}
