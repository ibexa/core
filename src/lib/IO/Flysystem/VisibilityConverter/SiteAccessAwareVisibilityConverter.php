<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\IO\Flysystem\VisibilityConverter;

use Ibexa\Contracts\Core\SiteAccess\ConfigResolverInterface;
use League\Flysystem\UnixVisibility\VisibilityConverter;
use League\Flysystem\Visibility;

/**
 * @internal
 *
 * SiteAccess-aware Flysystem Visibility Converter (strategy of BaseVisibilityConverter).
 * Relies on ConfigResolver to fetch expected public files and directories permission configuration.
 *
 * @see \Ibexa\Core\IO\Flysystem\VisibilityConverter\BaseVisibilityConverter
 * @see \League\Flysystem\Visibility
 * @see \League\Flysystem\UnixVisibility\PortableVisibilityConverter
 */
final class SiteAccessAwareVisibilityConverter extends BaseVisibilityConverter
{
    public const string SITE_CONFIG_IO_FILE_PERMISSIONS_PARAM_NAME = 'io.permissions.files';
    public const string SITE_CONFIG_IO_DIR_PERMISSIONS_PARAM_NAME = 'io.permissions.directories';

    private ConfigResolverInterface $configResolver;

    public function __construct(
        VisibilityConverter $nativeVisibilityConverter,
        ConfigResolverInterface $configResolver
    ) {
        parent::__construct($nativeVisibilityConverter);
        $this->configResolver = $configResolver;
    }

    protected function getPublicFilePermissions(): int
    {
        return $this->configResolver->getParameter(
            self::SITE_CONFIG_IO_FILE_PERMISSIONS_PARAM_NAME
        );
    }

    protected function getPublicDirectoryPermissions(): int
    {
        return $this->configResolver->getParameter(self::SITE_CONFIG_IO_DIR_PERMISSIONS_PARAM_NAME);
    }
}
