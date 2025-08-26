<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Bundle\Core\Imagine\Filter;

use Imagine\Exception\NotSupportedException;
use Imagine\Image\ImageInterface;

class UnsupportedFilter extends AbstractFilter
{
    /**
     * @throws \Imagine\Exception\NotSupportedException
     */
    public function apply(ImageInterface $image): never
    {
        throw new NotSupportedException('The filter is not supported by your current configuration.');
    }
}
