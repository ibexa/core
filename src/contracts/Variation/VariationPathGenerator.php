<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\Core\Variation;

/**
 * Generates the path to variations of original images.
 */
interface VariationPathGenerator
{
    /**
     * Returns the variation for image $path with $variation.
     */
    public function getVariationPath(string $path, string $variation): string;
}
