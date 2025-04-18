<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Bundle\Core\Imagine\Filter\Loader;

use Imagine\Exception\InvalidArgumentException;
use Imagine\Image\ImageInterface;

/**
 * Filter loader for geometry/scaleheight filter.
 * Proxy to RelativeResizeFilterLoader.
 */
class ScaleHeightFilterLoader extends FilterLoaderWrapped
{
    public const string IDENTIFIER = 'geometry/scaleheight';

    /**
     * @param array{int}|array{} $options Numerically indexed array. The first entry is height.
     *
     * @throws \Imagine\Exception\InvalidArgumentException
     */
    public function load(ImageInterface $image, array $options = []): ImageInterface
    {
        if (empty($options)) {
            throw new InvalidArgumentException('Missing width option');
        }

        return $this->innerLoader->load($image, ['heighten' => $options[0]]);
    }
}
