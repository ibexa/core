<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\IO;

/**
 * Modifies, both way, and URI.
 */
interface UrlDecorator
{
    /**
     * Decorates $uri.
     */
    public function decorate(string $uri): string;

    /**
     * Un-decorates decorated $uri.
     */
    public function undecorate(string $uri): string;
}
