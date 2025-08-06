<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Bundle\Core\Imagine\Filter;

use Ibexa\Contracts\Core\SiteAccess\ConfigResolverInterface;
use Liip\ImagineBundle\Imagine\Filter\FilterConfiguration as BaseFilterConfiguration;

class FilterConfiguration extends BaseFilterConfiguration
{
    private ConfigResolverInterface $configResolver;

    public function setConfigResolver(ConfigResolverInterface $configResolver): void
    {
        $this->configResolver = $configResolver;
    }

    /**
     * @return array<string, mixed>
     */
    public function get($filter): array
    {
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
     * @return array<string, mixed>
     */
    public function all(): array
    {
        return $this->configResolver->getParameter('image_variations') + parent::all();
    }

    /**
     * Returns filters to be used for $variationName.
     *
     * Both variations configured in Ibexa (SiteAccess context) and LiipImagineBundle are used.
     * Ibexa variations always have precedence.
     *
     * @param array<string, array{filters: array<mixed>}> $configuredVariations Variations set in eZ.
     *
     * @return array<mixed>
     */
    private function getVariationFilters(string $variationName, array $configuredVariations): array
    {
        if (!isset($configuredVariations[$variationName]['filters']) && !isset($this->filters[$variationName]['filters'])) {
            return [];
        }

        // Check variations configured in Ibexa config first.
        return $configuredVariations[$variationName]['filters'] ?? $this->filters[$variationName]['filters'];
    }

    /**
     * Returns post processors to be used for $variationName.
     *
     * Both variations configured in Ibexa and LiipImagineBundle are used.
     * Ibexa variations always have precedence.
     *
     * @param array<string, array{post_processor: array<mixed>}> $configuredVariations Variations set in Ibexa.
     *
     * @return array<mixed>
     */
    private function getVariationPostProcessors(string $variationName, array $configuredVariations): array
    {
        return $configuredVariations[$variationName]['post_processors']
            ?? $this->filters[$variationName]['post_processors']
            ?? [];
    }
}
