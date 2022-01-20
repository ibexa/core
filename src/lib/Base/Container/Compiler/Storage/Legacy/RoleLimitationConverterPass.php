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

/**
 * This compiler pass will register Legacy Storage role limitation converters.
 */
class RoleLimitationConverterPass implements CompilerPassInterface
{
    /**
     * @param \Symfony\Component\DependencyInjection\ContainerBuilder $container
     *
     * @throws \LogicException
     */
    public function process(ContainerBuilder $container)
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

class_alias(RoleLimitationConverterPass::class, 'eZ\Publish\Core\Base\Container\Compiler\Storage\Legacy\RoleLimitationConverterPass');
