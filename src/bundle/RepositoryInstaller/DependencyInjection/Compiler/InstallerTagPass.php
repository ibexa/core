<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Bundle\RepositoryInstaller\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * @deprecated 4.6.27 Installers are now injected into
 *             {@see \Ibexa\Bundle\RepositoryInstaller\Command\InstallPlatformCommand::$installers} via a
 *             `!tagged_locator` argument configured in services.yml, so this compiler pass is no longer needed.
 *             Will be removed in 5.0.
 */
class InstallerTagPass implements CompilerPassInterface
{
    /** @deprecated 4.6.27 Will be removed in 6.0. Use the 'ibexa.installer' string directly. */
    public const string INSTALLER_TAG = 'ibexa.installer';

    public function process(ContainerBuilder $container): void
    {
    }
}
