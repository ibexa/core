<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Core\FieldType\Image\IO;

use Ibexa\Contracts\Core\SiteAccess\ConfigResolverInterface;

/**
 * @internal
 */
class OptionsProvider
{
    protected ConfigResolverInterface $configResolver;

    public function __construct(ConfigResolverInterface $configResolver)
    {
        $this->configResolver = $configResolver;
    }

    protected function getSetting(string $name): ?string
    {
        return $this->configResolver->hasParameter($name)
            ? $this->configResolver->getParameter($name)
            : null;
    }

    public function getVarDir(): ?string
    {
        return $this->getSetting('var_dir');
    }

    public function getStorageDir(): ?string
    {
        return $this->getSetting('storage_dir');
    }

    public function getDraftImagesDir(): ?string
    {
        return $this->getSetting('image.versioned_images_dir');
    }

    public function getPublishedImagesDir(): ?string
    {
        return $this->getSetting('image.published_images_dir');
    }
}
