<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

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
     * @phpstan-param array{0?: int, 1?: int, 2?: string} $options
     */
    public function load(ImageInterface $image, array $options = []): ImageInterface
    {
        if (!isset($options[0], $options[1])) {
            throw new InvalidArgumentException('Invalid options for border filter. You must provide array(width, height)');
        }

        $color = static::DEFAULT_BORDER_COLOR;
        if (isset($options[2])) {
            [$width, $height, $color] = $options;
        } else {
            [$width, $height] = $options;
        }

        return (new Border($image->palette()->color($color), $width, $height))->apply($image);
    }
}
