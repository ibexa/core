<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Bundle\Core\Imagine\Filter\Loader;

use Imagine\Exception\InvalidArgumentException;
use Imagine\Filter\Advanced\Border;
use Imagine\Image\ImageInterface;
use Liip\ImagineBundle\Imagine\Filter\Loader\LoaderInterface;

/**
 * Loader for border filter.
 * Adds a border around the image.
 *
 * Note: Does not work properly with GD.
 */
class BorderFilterLoader implements LoaderInterface
{
    public const string IDENTIFIER = 'border';

    public const string DEFAULT_BORDER_COLOR = '#000';

    /**
     * @param array{int, int}|array{int, int, string}|array{} $options Values in the consecutive order: width, height, color.
     */
    public function load(ImageInterface $image, array $options = []): ImageInterface
    {
        $optionsCount = count($options);
        if ($optionsCount < 2) {
            throw new InvalidArgumentException('Invalid options for border filter. You must provide array(width, height)');
        }

        [$width, $height] = $options;
        $color = $options[2] ?? static::DEFAULT_BORDER_COLOR;

        return (new Border($image->palette()->color($color), $width, $height))->apply($image);
    }
}
