<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Bundle\RepositoryInstaller\DependencyInjection\Compiler;

use Ibexa\Bundle\RepositoryInstaller\Bootstrapper\DoctrineMigrationsSchemaHook;
use Ibexa\Bundle\RepositoryInstaller\Migration\TaggedMigrationsRunner;
use Ibexa\Contracts\DoctrineMigrations\Migrations\IbexaOnlyDependencyFactory;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Removes the {@see TaggedMigrationsRunner} service definition - and
 * {@see DoctrineMigrationsSchemaHook}, which cannot be built without it - when
 * {@see IbexaOnlyDependencyFactory::SERVICE_ID} isn't available - i.e. "ibexa/doctrine-migrations"
 * isn't installed/enabled - since {@see TaggedMigrationsRunner} requires a real
 * {@see \Doctrine\Migrations\DependencyFactory} and can no longer be built with none available.
 *
 * Runs before the reference-validity check (which happens in the "before removing" compiler pass
 * stage), so the still-mandatory "$dependencyFactory" argument on the (now-removed) definition
 * never gets a chance to fail container compilation.
 */
final class RemoveTaggedMigrationsRunnerPass implements CompilerPassInterface
{
    /**
     * @var list<string> definitions that cannot survive without a real DependencyFactory, in
     *                   dependent-first order
     */
    private const DEFINITIONS = [
        DoctrineMigrationsSchemaHook::class,
        TaggedMigrationsRunner::class,
    ];

    public function process(ContainerBuilder $container): void
    {
        if ($container->hasDefinition(IbexaOnlyDependencyFactory::SERVICE_ID)) {
            return;
        }

        foreach (self::DEFINITIONS as $id) {
            if ($container->hasDefinition($id)) {
                $container->removeDefinition($id);
            }
        }
    }
}
