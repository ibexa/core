<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Core\IO\Flysystem\PathPrefixer;

/**
 * @internal
 *
 * @experimental
 */
interface PathPrefixerInterface
{
    public function prefixPath(string $path): string;

    public function stripPrefix(string $path): string;

    public function stripDirectoryPrefix(string $path): string;

    public function prefixDirectoryPath(string $path): string;
}
