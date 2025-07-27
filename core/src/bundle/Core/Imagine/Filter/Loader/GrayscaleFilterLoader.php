<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Bundle\Core\Imagine\Filter\Loader;

use Imagine\Image\ImageInterface;
use Liip\ImagineBundle\Imagine\Filter\Loader\LoaderInterface;

/**
 * Grayscale filter loader.
 * Makes an image use grayscale.
 */
class GrayscaleFilterLoader implements LoaderInterface
{
    public const IDENTIFIER = 'colorspace/gray';

    public function load(ImageInterface $image, array $options = []): ImageInterface
    {
        $image->effects()->grayscale();

        return $image;
    }
}
