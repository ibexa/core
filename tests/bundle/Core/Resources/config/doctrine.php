<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Composer\InstalledVersions;

return static function (ContainerConfigurator $container): void {
    if (!isset($_ENV['DATABASE_URL'])) {
        $_ENV['DATABASE_URL'] = 'sqlite://:memory:';
    }

    $container->extension('doctrine', [
        'dbal' => [
            'url' => '%env(DATABASE_URL)%',
            'logging' => false,
        ],
        // DoctrineBundle 3 makes both settings the only allowed value and deprecates setting them
        'orm' => version_compare((string) InstalledVersions::getVersion('doctrine/doctrine-bundle'), '3.0.0', '>=')
            ? []
            : array_filter([
                'controller_resolver' => ['auto_mapping' => false],
                'enable_native_lazy_objects' => \PHP_VERSION_ID >= 80400 ? true : null,
            ], static fn ($v): bool => $v !== null),
    ]);
};
