<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\IO\Flysystem\VisibilityConverter;

use League\Flysystem\PortableVisibilityGuard;
use League\Flysystem\UnixVisibility\VisibilityConverter;
use League\Flysystem\Visibility;

/**
 * @internal
 *
 * Ibexa abstract Flysystem Visibility Converter. Supports public visibility only, by fetching
 * settings from strategy-based Visibility Converter. For private visibility relies on Flysystem's
 * native implementation of a Visibility Converter.
 *
 * @see \League\Flysystem\Visibility
 * @see \League\Flysystem\UnixVisibility\PortableVisibilityConverter
 */
abstract class BaseVisibilityConverter implements VisibilityConverter
{
    protected VisibilityConverter $nativeVisibilityConverter;

    public function __construct(VisibilityConverter $nativeVisibilityConverter)
    {
        $this->nativeVisibilityConverter = $nativeVisibilityConverter;
    }

    abstract protected function getPublicFilePermissions(): int;

    abstract protected function getPublicDirectoryPermissions(): int;

    final public function forFile(string $visibility): int
    {
        PortableVisibilityGuard::guardAgainstInvalidInput($visibility);

        return $visibility === Visibility::PUBLIC
            ? $this->getPublicFilePermissions()
            : $this->nativeVisibilityConverter->forFile($visibility);
    }

    final public function forDirectory(string $visibility): int
    {
        PortableVisibilityGuard::guardAgainstInvalidInput($visibility);

        return $visibility === Visibility::PUBLIC
            ? $this->getPublicDirectoryPermissions()
            : $this->nativeVisibilityConverter->forDirectory($visibility);
    }

    final public function inverseForFile(int $visibility): string
    {
        if ($visibility === $this->getPublicFilePermissions()) {
            return Visibility::PUBLIC;
        }

        if ($visibility === $this->nativeVisibilityConverter->forFile(Visibility::PRIVATE)) {
            return Visibility::PRIVATE;
        }

        return Visibility::PUBLIC; // default
    }

    final public function inverseForDirectory(int $visibility): string
    {
        if ($visibility === $this->getPublicDirectoryPermissions()) {
            return Visibility::PUBLIC;
        }

        if ($visibility === $this->nativeVisibilityConverter->forDirectory(Visibility::PRIVATE)) {
            return Visibility::PRIVATE;
        }

        return Visibility::PUBLIC; // default
    }

    final public function defaultForDirectories(): int
    {
        return $this->nativeVisibilityConverter->defaultForDirectories();
    }
}
