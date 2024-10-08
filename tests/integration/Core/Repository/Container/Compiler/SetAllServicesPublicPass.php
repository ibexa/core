<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Integration\Core\Repository\Container\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * This Pass overrides all services to be public.
 *
 * It is a workaround to the change in Symfony 4 which makes all services private by default.
 * Our integration tests are not prepared for this as they get services directly from the Container.
 *
 * WARNING! DO NOT USE IT IN YOUR APPLICATION.
 *
 * Inspired by \Symfony\Bundle\FrameworkBundle\DependencyInjection\Compiler\TestServiceContainerWeakRefPass
 *
 * @internal
 */
class SetAllServicesPublicPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $containerBuilder): void
    {
        $definitions = $containerBuilder->getDefinitions();
        foreach ($definitions as $id => $definition) {
            if (
                $id && '.' !== $id[0]
                && (!$definition->isPublic() || $definition->isPrivate())
                && !$definition->getErrors()
                && !$definition->isAbstract()
            ) {
                $definition->setPublic(true);
            }
        }

        $aliases = $containerBuilder->getAliases();
        foreach ($aliases as $id => $alias) {
            if ($id && '.' !== $id[0] && (!$alias->isPublic() || $alias->isPrivate())) {
                while (isset($aliases[$target = (string) $alias])) {
                    $alias = $aliases[$target];
                }
                if (
                    isset($definitions[$target])
                    && !$definitions[$target]->getErrors()
                    && !$definitions[$target]->isAbstract()
                ) {
                    $definition->setPublic(true);
                }
            }
        }
    }
}
