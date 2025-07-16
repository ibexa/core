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
 * Filter loader for geometry/scaleheightdownonly filter.
 * Proxy to ThumbnailFilterLoader.
 */
class ScaleHeightDownOnlyFilterLoader extends FilterLoaderWrapped
{
    public const string IDENTIFIER = 'geometry/scaleheightdownonly';

    /**
     * @phpstan-param array{0?: int} $options height
     */
    public function load(ImageInterface $image, array $options = []): ImageInterface
    {
        if (empty($options)) {
            throw new InvalidArgumentException('Missing height option');
        }

        return $this->innerLoader->load(
            $image,
            [
                'size' => [null, $options[0]],
                'mode' => 'inset',
            ]
        );
    }
}
