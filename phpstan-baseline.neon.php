<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

$includes = [];
if (PHP_VERSION_ID < 80000) {
    $includes[] = __DIR__ . '/phpstan-baseline-7.4.neon';
} else {
    $includes[] = __DIR__ . '/phpstan-baseline-gte-8.0.neon';
}

if (PHP_VERSION_ID >= 80100) {
    $includes[] = __DIR__ . '/phpstan-baseline-gte-8.1.neon';
}

if (PHP_VERSION_ID < 80200) {
    $includes[] = __DIR__ . '/phpstan-baseline-lte-8.1.neon';
}

if (PHP_VERSION_ID >= 80300) {
    $includes[] = __DIR__ . '/phpstan-baseline-gte-8.3.neon';
} else {
    $includes[] = __DIR__ . '/phpstan-baseline-lte-8.2.neon';
}

$config = [];
$config['includes'] = $includes;

return $config;
