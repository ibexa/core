<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\IO\Flysystem\PathPrefixer;

use const DIRECTORY_SEPARATOR;

/**
 * @internal
 */
abstract class BaseSiteAccessAwarePathPrefixer implements PathPrefixerInterface
{
    protected string $separator;

    public function __construct(string $separator = DIRECTORY_SEPARATOR)
    {
        $this->separator = $separator;
    }

    abstract protected function getSiteAccessAwarePathPrefix(): string;

    public function prefixPath(string $path): string
    {
        $siteAccessAwarePathPrefix = $this->getSiteAccessAwarePathPrefix();
        $prefix = rtrim($siteAccessAwarePathPrefix, '\\/');
        if ($prefix !== '' || $siteAccessAwarePathPrefix === $this->separator) {
            $prefix .= $this->separator;
        }

        return $prefix . ltrim($path, '\\/');
    }

    public function stripPrefix(string $path): string
    {
        return substr($path, strlen($this->getSiteAccessAwarePathPrefix()));
    }

    public function stripDirectoryPrefix(string $path): string
    {
        return rtrim($this->stripPrefix($path), '\\/');
    }

    public function prefixDirectoryPath(string $path): string
    {
        $prefixedPath = $this->prefixPath(rtrim($path, '\\/'));

        if ($prefixedPath === '' || (substr($prefixedPath, -1) === $this->separator)) {
            return $prefixedPath;
        }

        return $prefixedPath . $this->separator;
    }
}
