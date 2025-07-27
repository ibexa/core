<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Core\Base\Container\Compiler\Storage\Legacy;

use Ibexa\Core\Persistence\Legacy\User\Role\LimitationConverter;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class RoleLimitationConverterPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasDefinition(LimitationConverter::class)) {
            return;
        }

        $roleLimitationConverter = $container->getDefinition(LimitationConverter::class);

        foreach ($container->findTaggedServiceIds('ibexa.storage.legacy.role.limitation.handler') as $id => $attributes) {
            foreach ($attributes as $attribute) {
                $roleLimitationConverter->addMethodCall(
                    'addHandler',
                    [new Reference($id)]
                );
            }
        }
    }
}
