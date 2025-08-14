<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Bundle\Core\Imagine\Filter\Loader;

use Ibexa\Bundle\Core\Imagine\Filter\FilterInterface;
use Imagine\Image\ImageInterface;
use Liip\ImagineBundle\Imagine\Filter\Loader\LoaderInterface;

class SwirlFilterLoader implements LoaderInterface
{
    public const string IDENTIFIER = 'filter/swirl';

    private FilterInterface $filter;

    public function __construct(FilterInterface $filter)
    {
        $this->filter = $filter;
    }

    /**
     * @phpstan-param array{0?: int|float} $options degrees
     */
    public function load(ImageInterface $image, array $options = []): ImageInterface
    {
        if (!empty($options)) {
            $this->filter->setOption('degrees', $options[0]);
        }

        return $this->filter->apply($image);
    }
}
