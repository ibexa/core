<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Bundle\Core\Imagine\Filter\Loader;

use Imagine\Exception\InvalidArgumentException;
use Imagine\Image\ImageInterface;

/**
 * Filter loader for geometry/scalewidth filter.
 * Proxy to RelativeResizeFilterLoader.
 */
class ScaleWidthFilterLoader extends FilterLoaderWrapped
{
    public const IDENTIFIER = 'geometry/scalewidth';

    public function load(ImageInterface $image, array $options = []): ImageInterface
    {
        if (empty($options)) {
            throw new InvalidArgumentException('Missing width option');
        }

        return $this->innerLoader->load($image, ['widen' => $options[0]]);
    }
}
