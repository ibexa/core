<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Bundle\Core\Imagine\Filter\Imagick;

use Ibexa\Bundle\Core\Imagine\Filter\AbstractFilter;
use Imagine\Image\ImageInterface;
use Imagine\Imagick\Image;

class ReduceNoiseFilter extends AbstractFilter
{
    /**
     * @param ImageInterface|Image $image
     *
     * @return ImageInterface
     */
    public function apply(ImageInterface $image)
    {
        /** @var \Imagick $imagick */
        $imagick = $image->getImagick();
        $imagick->reduceNoiseImage((float)$this->getOption('radius', 0));

        return $image;
    }
}
