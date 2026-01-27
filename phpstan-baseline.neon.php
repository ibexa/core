<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

$includes = [];
if (PHP_VERSION_ID < 80400) {
    $includes[] = __DIR__ . '/phpstan-baseline-8.3.neon';
} else {
    $includes[] = __DIR__ . '/phpstan-baseline-8.4.neon';
}

$config = [];
$config['includes'] = $includes;

return $config;
