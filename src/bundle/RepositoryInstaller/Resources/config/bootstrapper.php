<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

use Ibexa\Bundle\RepositoryInstaller\Bootstrapper\DoctrineMigrationsSchemaHook;
use Ibexa\Bundle\RepositoryInstaller\Migration\TaggedMigrationsRunner;
use Ibexa\Contracts\Test\Core\Bootstrapper\HookInterface;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

// Integration-test bootstrap hooks (see ibexa/test-core's Bootstrapper). Loaded only in the "test"
// environment, and only when ibexa/test-core is actually installed - see
// IbexaRepositoryInstallerExtension::shouldRegisterBootstrapperHook().
return static function (ContainerConfigurator $configurator): void {
    $services = $configurator->services();

    $services->set(DoctrineMigrationsSchemaHook::class)
        ->private()
        // Mandatory: both this definition and TaggedMigrationsRunner's own are removed by
        // RemoveTaggedMigrationsRunnerPass when "ibexa/doctrine-migrations" isn't
        // installed/enabled, so compilation never attempts to resolve this reference then.
        ->arg('$taggedMigrationsRunner', service(TaggedMigrationsRunner::class))
        ->tag(HookInterface::TAG, ['priority' => DoctrineMigrationsSchemaHook::PRIORITY]);
};
