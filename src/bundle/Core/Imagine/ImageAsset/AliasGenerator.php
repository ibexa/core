<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Bundle\Core\Imagine\ImageAsset;

use Ibexa\Contracts\Core\FieldType\Value;
use Ibexa\Contracts\Core\Repository\ContentService;
use Ibexa\Contracts\Core\Repository\Values\Content\Field;
use Ibexa\Contracts\Core\Repository\Values\Content\VersionInfo;
use Ibexa\Contracts\Core\Variation\Values\Variation;
use Ibexa\Contracts\Core\Variation\VariationHandler;
use Ibexa\Core\FieldType\ImageAsset\AssetMapper;
use Ibexa\Core\FieldType\ImageAsset\Value as ImageAssetValue;

/**
 * Alias Generator Decorator allowing to generate variations based on passed ImageAsset\Value.
 */
class AliasGenerator implements VariationHandler
{
    private VariationHandler $innerAliasGenerator;

    private ContentService $contentService;

    private AssetMapper $assetMapper;

    public function __construct(
        VariationHandler $innerAliasGenerator,
        ContentService $contentService,
        AssetMapper $assetMapper
    ) {
        $this->innerAliasGenerator = $innerAliasGenerator;
        $this->contentService = $contentService;
        $this->assetMapper = $assetMapper;
    }

    public function getVariation(Field $field, VersionInfo $versionInfo, string $variationName, array $parameters = []): Variation
    {
        if ($this->supportsValue($field->value)) {
            $destinationContent = $this->contentService->loadContent(
                (int)$field->value->destinationContentId
            );

            return $this->innerAliasGenerator->getVariation(
                $this->assetMapper->getAssetField($destinationContent),
                $destinationContent->versionInfo,
                $variationName,
                $parameters
            );
        }

        return $this->innerAliasGenerator->getVariation($field, $versionInfo, $variationName, $parameters);
    }

    /**
     * Returns TRUE if the value is supported by alias generator.
     *
     * @param \Ibexa\Contracts\Core\FieldType\Value $value
     *
     * @return bool
     */
    public function supportsValue(Value $value): bool
    {
        return $value instanceof ImageAssetValue;
    }
}
