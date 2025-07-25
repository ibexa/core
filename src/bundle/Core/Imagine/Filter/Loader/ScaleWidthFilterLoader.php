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
 * Filter loader for geometry/scalewidth filter.
 * Proxy to RelativeResizeFilterLoader.
 */
class ScaleWidthFilterLoader extends FilterLoaderWrapped
{
    public const string IDENTIFIER = 'geometry/scalewidth';

    /**
     * @phpstan-param array{0?: int} $options width
     */
    public function load(ImageInterface $image, array $options = []): ImageInterface
    {
        if (empty($options)) {
            throw new InvalidArgumentException('Missing width option');
        }

        return $this->innerLoader->load($image, ['widen' => $options[0]]);
    }
}
