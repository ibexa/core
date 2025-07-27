<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\IO\Flysystem\PathPrefixer;

use const DIRECTORY_SEPARATOR;
use Ibexa\Core\IO\IOConfigProvider;

/**
 * @internal
 */
final class LocalSiteAccessAwarePathPrefixer extends BaseSiteAccessAwarePathPrefixer
{
    private IOConfigProvider $ioConfigProvider;

    public function __construct(
        IOConfigProvider $ioConfigProvider,
        string $separator = DIRECTORY_SEPARATOR
    ) {
        parent::__construct($separator);
        $this->ioConfigProvider = $ioConfigProvider;
    }

    protected function getSiteAccessAwarePathPrefix(): string
    {
        return $this->ioConfigProvider->getRootDir();
    }
}
