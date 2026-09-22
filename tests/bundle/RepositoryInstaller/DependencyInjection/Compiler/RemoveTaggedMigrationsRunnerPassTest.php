<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Bundle\RepositoryInstaller\DependencyInjection\Compiler;

use Ibexa\Bundle\RepositoryInstaller\Bootstrapper\DoctrineMigrationsSchemaHook;
use Ibexa\Bundle\RepositoryInstaller\DependencyInjection\Compiler\RemoveTaggedMigrationsRunnerPass;
use Ibexa\Bundle\RepositoryInstaller\Migration\TaggedMigrationsRunner;
use Ibexa\Contracts\DoctrineMigrations\Migrations\IbexaOnlyDependencyFactory;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

/**
 * @covers \Ibexa\Bundle\RepositoryInstaller\DependencyInjection\Compiler\RemoveTaggedMigrationsRunnerPass
 */
final class RemoveTaggedMigrationsRunnerPassTest extends TestCase
{
    public function testRemovesRunnerAndItsDependentsWhenDependencyFactoryIsMissing(): void
    {
        $container = $this->createContainer();

        (new RemoveTaggedMigrationsRunnerPass())->process($container);

        self::assertFalse($container->hasDefinition(TaggedMigrationsRunner::class));
        self::assertFalse($container->hasDefinition(DoctrineMigrationsSchemaHook::class));
    }

    public function testKeepsRunnerAndItsDependentsWhenDependencyFactoryIsDefined(): void
    {
        $container = $this->createContainer();
        $container->setDefinition(IbexaOnlyDependencyFactory::SERVICE_ID, new Definition());

        (new RemoveTaggedMigrationsRunnerPass())->process($container);

        self::assertTrue($container->hasDefinition(TaggedMigrationsRunner::class));
        self::assertTrue($container->hasDefinition(DoctrineMigrationsSchemaHook::class));
    }

    /**
     * The hook is only registered in the "test" environment, so the runner routinely has to be
     * removed with no hook definition alongside it.
     */
    public function testRemovesTheRunnerWhenTheHookIsNotRegisteredAtAll(): void
    {
        $container = new ContainerBuilder();
        $container->setDefinition(TaggedMigrationsRunner::class, new Definition());

        (new RemoveTaggedMigrationsRunnerPass())->process($container);

        self::assertFalse($container->hasDefinition(TaggedMigrationsRunner::class));
    }

    private function createContainer(): ContainerBuilder
    {
        $container = new ContainerBuilder();
        $container->setDefinition(TaggedMigrationsRunner::class, new Definition());
        $container->setDefinition(DoctrineMigrationsSchemaHook::class, new Definition());

        return $container;
    }
}
