<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Bundle\Core\Imagine\Filter\Gmagick;

use Ibexa\Bundle\Core\Imagine\Filter\AbstractFilter;
use Imagine\Gmagick\Image;
use Imagine\Image\ImageInterface;

class ReduceNoiseFilter extends AbstractFilter
{
    /**
     * @throws \GmagickException
     */
    public function apply(ImageInterface $image): ImageInterface
    {
        if ($image instanceof Image) {
            $gmagick = $image->getGmagick();
            $gmagick->reduceNoiseImage((float)$this->getOption('radius', 0));
        }

        return $image;
    }
}
