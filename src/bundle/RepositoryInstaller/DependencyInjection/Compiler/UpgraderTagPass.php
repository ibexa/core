<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
namespace Ibexa\Bundle\RepositoryInstaller\DependencyInjection\Compiler;

use Ibexa\Bundle\RepositoryInstaller\Command\UpgradePlatformCommand;
use LogicException;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Injects services tagged as "ibexa.installer" into
 * {@see \Ibexa\Bundle\RepositoryInstaller\Command\UpgradePlatformCommand::$installers}.
 */
class UpgraderTagPass implements CompilerPassInterface
{
    public const INSTALLER_TAG = 'ibexa.upgrader';

    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition(UpgradePlatformCommand::class)) {
            return;
        }

        $installCommandDef = $container->findDefinition(UpgradePlatformCommand::class);
        $installers = [];

        foreach ($container->findTaggedServiceIds(self::INSTALLER_TAG) as $id => $tags) {
            foreach ($tags as $tag) {
                if (!isset($tag['type'])) {
                    throw new LogicException(
                        sprintf(
                            'Service tag %s needs a "type" attribute to identify the installer. You need to provide a tag for %s.',
                            self::INSTALLER_TAG,
                            $id
                        )
                    );
                }

                $installers[$tag['type']] = new Reference($id);
            }
        }

        $installCommandDef->replaceArgument('$installers', $installers);
    }
}

class_alias(UpgraderTagPass::class, 'EzSystems\PlatformInstallerBundle\DependencyInjection\Compiler\UpgraderTagPass');
