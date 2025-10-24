<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\IO;

use Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException;

/**
 * Converts an URL from one decorator to another.
 *
 * ```php
 * $redecorator = new UrlRedecorator(
 *   new Prefix('a'),
 *   new Prefix('b')
 * );
 *
 * $redecorator->redecorateFromSource('a/url');
 * // 'b/url'
 *
 * $redecorator->redecorateFromTarget('b/url');
 * // 'a/url'
 * ```
 */
interface UrlRedecoratorInterface
{
    /**
     * @throws InvalidArgumentException If $uri couldn't be interpreted by the target decorator
     */
    public function redecorateFromSource(string $uri): string;

    /**
     * @throws InvalidArgumentException If $uri couldn't be interpreted by the target decorator
     */
    public function redecorateFromTarget(string $uri): string;
}
