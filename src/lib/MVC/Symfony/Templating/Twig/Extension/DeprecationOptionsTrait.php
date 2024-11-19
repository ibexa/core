<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
namespace Ibexa\Core\MVC\Symfony\Templating\Twig\Extension;

use Twig\DeprecatedCallableInfo;

/**
 * This trait provides ability to deprecate twig functions, maintaining compatibility with twig/twig prior to 3.15.
 */
trait DeprecationOptionsTrait
{
    /**
     * @phpstan-param non-empty-string $newFunction
     *
     * @phpstan-return array{
     *     deprecation_info: \Twig\DeprecatedCallableInfo,
     * }|array{
     *     deprecated: non-empty-string,
     *     deprecating_package: non-empty-string,
     *     alternative: non-empty-string,
     * }
     */
    private function getDeprecationOptions(string $newFunction): array
    {
        if (class_exists(DeprecatedCallableInfo::class)) {
            return [
                'deprecation_info' => new DeprecatedCallableInfo('ibexa/core', '4.0', $newFunction),
            ];
        }

        // Compatibility with twig/twig prior to 3.15
        return [
            'deprecated' => '4.0',
            'deprecating_package' => 'ibexa/core',
            'alternative' => $newFunction,
        ];
    }
}
