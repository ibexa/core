<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Bundle\Core\Imagine\VariationPathGenerator;

use Ibexa\Contracts\Core\Variation\VariationPathGenerator;
use Liip\ImagineBundle\Imagine\Filter\FilterConfiguration;

/**
 * Decorates VariationPathGenerator with .webp extension if image variation is configured for this format.
 */
final class WebpFormatVariationPathGenerator implements VariationPathGenerator
{
    private VariationPathGenerator $innerVariationPathGenerator;

    private FilterConfiguration $filterConfiguration;

    public function __construct(
        VariationPathGenerator $innerVariationPathGenerator,
        FilterConfiguration $filterConfiguration
    ) {
        $this->innerVariationPathGenerator = $innerVariationPathGenerator;
        $this->filterConfiguration = $filterConfiguration;
    }

    public function getVariationPath(
        string $path,
        string $variation
    ): string {
        $variationPath = $this->innerVariationPathGenerator->getVariationPath($path, $variation);
        $filterConfig = $this->filterConfiguration->get($variation);

        if (!isset($filterConfig['format']) || $filterConfig['format'] !== 'webp') {
            return $variationPath;
        }

        return $variationPath . '.webp';
    }
}
