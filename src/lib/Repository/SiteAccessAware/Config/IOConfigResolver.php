<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\Repository\SiteAccessAware\Config;

use Ibexa\Core\IO\IOConfigProvider;

/**
 * @internal
 */
final class IOConfigResolver implements IOConfigProvider
{
    private string $storageDir;

    private string $legacyUrlPrefix;

    private string $urlPrefix;

    public function __construct(
        string $storageDir,
        string $legacyUrlPrefix,
        string $urlPrefix
    ) {
        $this->storageDir = $storageDir;
        $this->legacyUrlPrefix = $legacyUrlPrefix;
        $this->urlPrefix = $urlPrefix;
    }

    public function getRootDir(): string
    {
        return $this->storageDir;
    }

    public function getLegacyUrlPrefix(): string
    {
        return $this->legacyUrlPrefix;
    }

    public function getUrlPrefix(): string
    {
        return $this->urlPrefix;
    }
}
