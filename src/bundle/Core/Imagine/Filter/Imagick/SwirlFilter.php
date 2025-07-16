<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Bundle\Core\Imagine\Filter\Imagick;

use Ibexa\Bundle\Core\Imagine\Filter\AbstractFilter;
use Imagine\Image\ImageInterface;
use Imagine\Imagick\Image;

class SwirlFilter extends AbstractFilter
{
    /**
     * @throws \ImagickException
     */
    public function apply(ImageInterface $image): Image|ImageInterface
    {
        if ($image instanceof Image) {
            $imagick = $image->getImagick();
            $imagick->swirlImage((float)$this->getOption('degrees', 60));
        }

        return $image;
    }
}
