<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

use Composer\InstalledVersions;
use Composer\Semver\VersionParser;

$includes = [];

if (PHP_VERSION_ID >= 80400) {
    $includes[] = __DIR__ . '/phpstan-baseline-gte-8.4.neon';
} else {
    $includes[] = __DIR__ . '/phpstan-baseline-lte-8.3.neon';
}

if (InstalledVersions::satisfies(new VersionParser(), 'league/flysystem', '2.*')) {
    $includes[] = __DIR__ . '/phpstan-baseline-flysystem-v2.neon';
}

return [
    'includes' => $includes,
];
