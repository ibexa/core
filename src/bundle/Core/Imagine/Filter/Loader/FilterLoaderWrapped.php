<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Bundle\Core\Imagine\Filter\Loader;

use Liip\ImagineBundle\Imagine\Filter\Loader\LoaderInterface;

abstract class FilterLoaderWrapped implements LoaderInterface
{
    protected LoaderInterface $innerLoader;

    public function setInnerLoader(LoaderInterface $innerLoader): void
    {
        $this->innerLoader = $innerLoader;
    }
}
