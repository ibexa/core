<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Bundle\Core\Imagine\Filter\Loader;

use Imagine\Exception\InvalidArgumentException;
use Imagine\Image\ImageInterface;

/**
 * Filter loader for geometry/scaledownonly filter.
 * Proxy to ThumbnailFilterLoader.
 */
class ScaleDownOnlyFilterLoader extends FilterLoaderWrapped
{
    public const IDENTIFIER = 'geometry/scaledownonly';

    /**
     * Loads and applies a filter on the given image.
     *
     * @param ImageInterface $image
     * @param array $options Numerically indexed array. First entry is width, second is height.
     *
     * @throws InvalidArgumentException
     *
     * @return ImageInterface
     */
    public function load(
        ImageInterface $image,
        array $options = []
    ): ImageInterface {
        if (count($options) < 2) {
            throw new InvalidArgumentException('Missing width and/or height options');
        }

        return $this->innerLoader->load(
            $image,
            [
                'size' => $options,
                'mode' => 'inset',
            ]
        );
    }
}
