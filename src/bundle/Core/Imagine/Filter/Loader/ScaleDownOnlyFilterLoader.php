<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Bundle\Core\Imagine\Filter\Loader;

use Imagine\Exception\InvalidArgumentException;
use Imagine\Image\ImageInterface;

/**
 * Filter loader for geometry/scaledownonly filter.
 * Proxy to ThumbnailFilterLoader.
 */
class ScaleDownOnlyFilterLoader extends FilterLoaderWrapped
{
    public const string IDENTIFIER = 'geometry/scaledownonly';

    /**
     * Loads and applies a filter on the given image.
     *
     * @param array{0?: int, 1?: int} $options Numerically indexed array. The First entry is width, the second is height.
     *
     * @throws \Imagine\Exception\InvalidArgumentException
     */
    public function load(ImageInterface $image, array $options = []): ImageInterface
    {
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
