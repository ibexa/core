<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Bundle\Core\Variation;

use Ibexa\Contracts\Core\Repository\Values\Content\Field;
use Ibexa\Contracts\Core\Repository\Values\Content\VersionInfo;
use Ibexa\Contracts\Core\SiteAccess\ConfigResolverInterface;
use Ibexa\Contracts\Core\Variation\Values\Variation;
use Ibexa\Contracts\Core\Variation\VariationHandler;
use Ibexa\Core\Variation\VariationHandlerRegistry;

final class VariationHandlerResolver implements VariationHandler
{
    private VariationHandlerRegistry $variationHandlerRegistry;

    private ConfigResolverInterface $configResolver;

    public function __construct(
        VariationHandlerRegistry $variationHandlerRegistry,
        ConfigResolverInterface $configResolver
    ) {
        $this->variationHandlerRegistry = $variationHandlerRegistry;
        $this->configResolver = $configResolver;
    }

    public function getVariation(
        Field $field,
        VersionInfo $versionInfo,
        string $variationName,
        array $parameters = []
    ): Variation {
        $variationHandlerIdentifier = $this->configResolver->getParameter('variation_handler_identifier');
        $handler = $this->variationHandlerRegistry->getVariationHandler($variationHandlerIdentifier);

        return $handler->getVariation(
            $field,
            $versionInfo,
            $variationName,
            $parameters
        );
    }
}
