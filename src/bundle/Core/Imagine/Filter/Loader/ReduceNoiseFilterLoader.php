<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Bundle\Core\Imagine\Filter\Loader;

use Ibexa\Bundle\Core\Imagine\Filter\FilterInterface;
use Imagine\Exception\NotSupportedException;
use Imagine\Gmagick\Image as GmagickImage;
use Imagine\Image\ImageInterface;
use Imagine\Imagick\Image as ImagickImage;
use Liip\ImagineBundle\Imagine\Filter\Loader\LoaderInterface;

/**
 * Noise reduction filter loader.
 * Only works with Imagick / Gmagick.
 */
class ReduceNoiseFilterLoader implements LoaderInterface
{
    public const string IDENTIFIER = 'filter/noise';

    private FilterInterface $filter;

    public function __construct(FilterInterface $filter)
    {
        $this->filter = $filter;
    }

    /**
     * @phpstan-param array{0?: int} $options radius
     */
    public function load(ImageInterface $image, array $options = []): ImageInterface
    {
        if (!$image instanceof ImagickImage && !$image instanceof GmagickImage) {
            throw new NotSupportedException('ReduceNoiseFilterLoader is only compatible with "imagick" and "gmagick" drivers');
        }

        if (!empty($options)) {
            $this->filter->setOption('radius', $options[0]);
        }

        return $this->filter->apply($image);
    }
}
