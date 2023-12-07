<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\IO\Flysystem\VisibilityConverter;

use League\Flysystem\UnixVisibility\VisibilityConverter;
use League\Flysystem\Visibility;

/**
 * @internal
 *
 * SiteAccess-aware Flysystem Visibility Converter (strategy of BaseVisibilityConverter).
 * Relies on configured DFS permissions, fall-backing to Flysystem's native implementation of
 * a Visibility Converter, if not configured.
 *
 * @see \Ibexa\Core\IO\Flysystem\VisibilityConverter\BaseVisibilityConverter
 * @see \League\Flysystem\Visibility
 * @see \League\Flysystem\UnixVisibility\PortableVisibilityConverter
 */
final class DFSVisibilityConverter extends BaseVisibilityConverter
{
    /** @var array{files: int, directories: int} */
    private array $permissions;

    /**
     * @param array{files: int, directories: int} $permissions
     */
    public function __construct(
        VisibilityConverter $nativeVisibilityConverter,
        array $permissions
    ) {
        parent::__construct($nativeVisibilityConverter);
        $this->permissions = $permissions;
    }

    protected function getPublicFilePermissions(): int
    {
        return $this->permissions['files'] ?? $this->nativeVisibilityConverter->forFile(
            Visibility::PUBLIC
        );
    }

    protected function getPublicDirectoryPermissions(): int
    {
        return $this->permissions['directories'] ?? $this->nativeVisibilityConverter->forDirectory(
            Visibility::PUBLIC
        );
    }
}
