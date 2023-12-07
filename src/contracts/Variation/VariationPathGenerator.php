<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
namespace Ibexa\Contracts\Core\Variation;

/**
 * Generates the path to variations of original images.
 */
interface VariationPathGenerator
{
    /**
     * Returns the variation for image $path with $variation.
     *
     * @param string $path
     * @param string $variation
     *
     * @return string
     */
    public function getVariationPath($path, $variation);
}
