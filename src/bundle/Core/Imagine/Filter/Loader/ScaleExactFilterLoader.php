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
 * Filter loader for geometry/scaleexact filter.
 * Proxy to ThumbnailFilterLoader.
 */
class ScaleExactFilterLoader extends FilterLoaderWrapped
{
    public const string IDENTIFIER = 'geometry/scaleexact';

    /**
     * @phpstan-param array{0?: int, 1?: int} $options width, height
     */
    public function load(ImageInterface $image, array $options = []): ImageInterface
    {
        if (count($options) < 2) {
            throw new InvalidArgumentException('Missing width and/or height options');
        }

        return $this->innerLoader->load($image, ['size' => $options]);
    }
}
