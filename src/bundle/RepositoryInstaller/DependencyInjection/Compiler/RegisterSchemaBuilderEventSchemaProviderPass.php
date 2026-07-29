<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Bundle\RepositoryInstaller\DependencyInjection\Compiler;

use Doctrine\Migrations\Provider\SchemaProvider;
use Ibexa\Bundle\RepositoryInstaller\Migration\SchemaBuilderEventSchemaProvider;
use Ibexa\Contracts\DoctrineMigrations\Migrations\IbexaOnlyDependencyFactory;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Wires {@see SchemaBuilderEventSchemaProvider} onto
 * {@see IbexaOnlyDependencyFactory::SERVICE_ID} as its {@see SchemaProvider}, so
 * "ibexa:doctrine:migrations:diff"/":generate" (registered by ibexa/doctrine-migrations) can
 * compare against the schema Ibexa's SchemaBuilderEvent-based packages expect.
 *
 * A no-op if either ibexa/doctrine-migrations or ibexa/doctrine-schema isn't installed/enabled --
 * both are optional as far as this bundle is concerned.
 */
final class RegisterSchemaBuilderEventSchemaProviderPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (
            !$container->hasDefinition(IbexaOnlyDependencyFactory::SERVICE_ID)
            || !$container->hasDefinition(SchemaBuilderEventSchemaProvider::class)
        ) {
            return;
        }

        $container->getDefinition(IbexaOnlyDependencyFactory::SERVICE_ID)
            ->addMethodCall('setService', [SchemaProvider::class, new Reference(SchemaBuilderEventSchemaProvider::class)]);
    }
}
