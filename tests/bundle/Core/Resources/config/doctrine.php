<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

return static function (ContainerConfigurator $container): void {
    if (!isset($_ENV['DATABASE_URL'])) {
        $_ENV['DATABASE_URL'] = 'sqlite://:memory:';
    }

    $container->extension('doctrine', [
        'dbal' => [
            'url' => '%env(DATABASE_URL)%',
            'logging' => false,
            'use_savepoints' => true,
        ],
    ]);
};
