<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Bundle\Core\Imagine\Filter;

use Ibexa\Bundle\Core\DependencyInjection\Configuration\Parser\Image;
use Ibexa\Contracts\Core\SiteAccess\ConfigResolverInterface;
use Liip\ImagineBundle\Imagine\Filter\FilterConfiguration as BaseFilterConfiguration;

/**
 * @phpstan-import-type TImageVariations from Image
 * @phpstan-import-type TFilters from Image
 * @phpstan-import-type TPostProcessors from Image
 */
class FilterConfiguration extends BaseFilterConfiguration
{
    private ConfigResolverInterface $configResolver;

    public function setConfigResolver(ConfigResolverInterface $configResolver): void
    {
        $this->configResolver = $configResolver;
    }

    /**
     * @phpstan-return array{
     *     cache?: string|null,
     *     data_loader?: string|null,
     *     reference?: string|null,
     *     filters?: TFilters,
     *     post_processors?: TPostProcessors,
     *     quality?: int|null,
     *     jpeg_quality?: int|null,
     *     png_compression_level?: int|null,
     *     png_compression_filter?: int|null,
     *     format?: string|null,
     *     animated?: bool,
     *     default_image?: string|null
     * }
     */
    public function get($filter): array
    {
        /** @phpstan-var TImageVariations $configuredVariations */
        $configuredVariations = $this->configResolver->getParameter('image_variations');
        if (!array_key_exists($filter, $configuredVariations)) {
            return parent::get($filter);
        }

        $filterConfig = isset($this->filters[$filter]) ? parent::get($filter) : [];

        return [
            'cache' => 'ibexa',
            'data_loader' => 'ibexa',
            'reference' => $configuredVariations[$filter]['reference'] ?? null,
            'filters' => $this->getVariationFilters($filter, $configuredVariations),
            'post_processors' => $this->getVariationPostProcessors($filter, $configuredVariations),
        ] + $filterConfig;
    }

    /**
     * @phpstan-return TImageVariations
     */
    public function all(): array
    {
        /** @phpstan-var TImageVariations $configuredVariations */
        $configuredVariations = $this->configResolver->getParameter('image_variations');

        return $configuredVariations + parent::all();
    }

    /**
     * Returns filters to be used for $variationName.
     *
     * Both variations configured in Ibexa (SiteAccess context) and LiipImagineBundle are used.
     * Ibexa variations always have precedence.
     *
     * @phpstan-param TImageVariations $configuredVariations Variations' set.
     *
     * @phpstan-return TFilters
     */
    private function getVariationFilters(string $variationName, array $configuredVariations): array
    {
        if (!isset($configuredVariations[$variationName]['filters']) && !isset($this->filters[$variationName]['filters'])) {
            return [];
        }

        // Prioritize variations configured in Ibexa config
        return $configuredVariations[$variationName]['filters'] ?? $this->filters[$variationName]['filters'];
    }

    /**
     * Returns post processors to be used for $variationName.
     *
     * Both variations configured in Ibexa and LiipImagineBundle are used.
     * Ibexa variations always have precedence.
     *
     * @phpstan-param TImageVariations $configuredVariations Variations set in eZ.
     *
     * @phpstan-return TPostProcessors
     */
    private function getVariationPostProcessors(string $variationName, array $configuredVariations): array
    {
        return $configuredVariations[$variationName]['post_processors']
            ?? $this->filters[$variationName]['post_processors']
            ?? [];
    }
}
