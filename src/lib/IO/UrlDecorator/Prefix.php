<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\IO\UrlDecorator;

use Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException;
use Ibexa\Core\IO\Exception\InvalidBinaryPrefixException;
use Ibexa\Core\IO\IOConfigProvider;
use Ibexa\Core\IO\UrlDecorator;

/**
 * Prefixes the URI with a string. Ensures an initial / in the parameter.
 */
class Prefix implements UrlDecorator
{
    public function __construct(protected readonly IOConfigProvider $ioConfigResolver) {}

    public function getPrefix(): string
    {
        $prefix = $this->ioConfigResolver->getLegacyUrlPrefix();

        return trim($prefix, '/') . '/';
    }

    public function decorate(string $uri): string
    {
        $prefix = $this->getPrefix();
        if (empty($prefix)) {
            return $uri;
        }

        return $prefix . trim($uri, '/');
    }

    /**
     * @throws InvalidArgumentException
     */
    public function undecorate(string $uri): string
    {
        $prefix = $this->getPrefix();
        if (empty($prefix)) {
            return $uri;
        }

        if (!str_starts_with($uri, $prefix)) {
            throw new InvalidBinaryPrefixException($uri, $prefix);
        }

        return trim(substr($uri, strlen($prefix)), '/');
    }
}
