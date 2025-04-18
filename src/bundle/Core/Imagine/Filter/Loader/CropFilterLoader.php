<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Bundle\Core\Imagine\Filter\Loader;

use Imagine\Exception\InvalidArgumentException;
use Imagine\Image\ImageInterface;

/**
 * Filter loader for geometry/crop filter.
 * Proxy to CropFilterLoader.
 */
class CropFilterLoader extends FilterLoaderWrapped
{
    public const string IDENTIFIER = 'geometry/crop';

    /**
     * @param array{int, int, int, int}|array{} $options Numerically indexed array. Values in the consecutive order: height, width, offsetX (horizontal), offsetY (vertical).
     */
    public function load(ImageInterface $image, array $options = []): ImageInterface
    {
        if (count($options) < 4) {
            throw new InvalidArgumentException('Invalid options for geometry/crop filter. You must provide array(width, height, offsetX, offsetY)');
        }

        return $this->innerLoader->load(
            $image,
            [
                'size' => [$options[0], $options[1]],
                'start' => [$options[2], $options[3]],
            ]
        );
    }
}
