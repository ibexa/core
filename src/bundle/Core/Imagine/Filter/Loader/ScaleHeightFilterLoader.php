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
 * Filter loader for geometry/scaleheight filter.
 * Proxy to RelativeResizeFilterLoader.
 */
class ScaleHeightFilterLoader extends FilterLoaderWrapped
{
    public const string IDENTIFIER = 'geometry/scaleheight';

    /**
     * @phpstan-param array{0?: int} $options height
     */
    public function load(ImageInterface $image, array $options = []): ImageInterface
    {
        if (empty($options)) {
            throw new InvalidArgumentException('Missing height option');
        }

        return $this->innerLoader->load($image, ['heighten' => $options[0]]);
    }
}
