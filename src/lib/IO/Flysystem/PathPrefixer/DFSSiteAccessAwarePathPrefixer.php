<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\IO\Flysystem\PathPrefixer;

use const DIRECTORY_SEPARATOR;

use Ibexa\Contracts\Core\SiteAccess\ConfigProcessor;

/**
 * @internal
 */
final class DFSSiteAccessAwarePathPrefixer extends BaseSiteAccessAwarePathPrefixer
{
    private ConfigProcessor $configProcessor;

    private string $rootDir;

    private string $path;

    public function __construct(
        ConfigProcessor $configProcessor,
        string $rootDir,
        string $path,
        string $separator = DIRECTORY_SEPARATOR
    ) {
        parent::__construct($separator);

        $this->rootDir = $rootDir;
        $this->path = $path;
        $this->configProcessor = $configProcessor;
    }

    protected function getSiteAccessAwarePathPrefix(): string
    {
        return sprintf(
            '%s%s%s',
            rtrim($this->rootDir, $this->separator),
            $this->separator,
            $this->configProcessor->processSettingValue($this->path)
        );
    }
}
