<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Bundle\IO\DependencyInjection\Compiler;

use Ibexa\Bundle\IO\Migration\FileListerRegistry\ConfigurableRegistry;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

final class MigrationFileListerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (!$container->has(ConfigurableRegistry::class)) {
            return;
        }

        $fileListersTagged = $container->findTaggedServiceIds('ibexa.io.migration.file_lister');

        $fileListers = [];
        foreach ($fileListersTagged as $id => $tags) {
            foreach ($tags as $attributes) {
                $fileListers[$attributes['identifier']] = new Reference($id);
            }
        }

        $fileListerRegistryDef = $container->findDefinition(ConfigurableRegistry::class);
        $fileListerRegistryDef->setArguments([$fileListers]);
    }
}
