<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Bundle\Core\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Configures session handler based on `ibexa.session.handler_id`
 * and `ibexa.session.save_path`.
 *
 * This ensures parameters have the highest priority and the configuration
 * will be respected with default framework.yaml file.
 *
 * @internal
 */
final class SessionConfigurationPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        $handlerId = $container->hasParameter('ibexa.session.handler_id')
            ? $container->getParameter('ibexa.session.handler_id')
            : null;

        $savePath = $container->hasParameter('ibexa.session.save_path')
            ? $container->getParameter('ibexa.session.save_path')
            : null;

        if (null !== $handlerId) {
            $usedEnvs = [];
            $container->resolveEnvPlaceholders($handlerId, null, $usedEnvs);

            // code below follows FrameworkExtension from symfony/framework-bundle
            if ($usedEnvs || preg_match('#^[a-z]++://#', $handlerId)) {
                $id = '.cache_connection.' . ContainerBuilder::hash($handlerId);

                $container->getDefinition('session.abstract_handler')
                    ->replaceArgument(
                        0,
                        $container->has($id)
                            ? new Reference($id)
                            : $handlerId
                    );

                $container->setAlias('session.handler', 'session.abstract_handler');
            } else {
                $container->setAlias('session.handler', $handlerId);
            }

            if ($container->hasDefinition('session.storage.native')) {
                $container
                    ->getDefinition('session.storage.native')
                    ->replaceArgument(1, new Reference('session.handler'));
            }

            if ($container->hasDefinition('session.storage.php_bridge')) {
                $container
                    ->getDefinition('session.storage.php_bridge')
                    ->replaceArgument(0, new Reference('session.handler'));
            }

            if ($container->hasDefinition('session.storage.factory.native')) {
                $container
                    ->getDefinition('session.storage.factory.native')
                    ->replaceArgument(1, new Reference('session.handler'));
            }

            if ($container->hasDefinition('session.storage.factory.php_bridge')) {
                $container
                    ->getDefinition('session.storage.factory.php_bridge')
                    ->replaceArgument(0, new Reference('session.handler'));
            }
        }

        if (null !== $savePath) {
            $container->setParameter('session.save_path', $savePath);
        }
    }
}
