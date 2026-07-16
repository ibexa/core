<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Core\MVC\Symfony\Templating\Twig\Extension;

use Ibexa\Contracts\Core\Repository\Exceptions\InvalidVariationException;
use Ibexa\Contracts\Core\Repository\Values\Content\Field;
use Ibexa\Contracts\Core\Repository\Values\Content\VersionInfo;
use Ibexa\Contracts\Core\Variation\Values\Variation;
use Ibexa\Contracts\Core\Variation\VariationHandler;
use Ibexa\Core\FieldType\ImageAsset\AssetMapper;
use Ibexa\Core\MVC\Exception\SourceImageNotFoundException;
use InvalidArgumentException;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class ImageExtension extends AbstractExtension implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    /** @var VariationHandler */
    private $imageVariationService;

    /** @var AssetMapper */
    protected $assetMapper;

    public function __construct(
        VariationHandler $imageVariationService,
        AssetMapper $assetMapper
    ) {
        $this->imageVariationService = $imageVariationService;
        $this->assetMapper = $assetMapper;
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction(
                'ibexa_image_alias',
                $this->getImageVariation(...),
                ['is_safe' => ['html']]
            ),
            new TwigFunction(
                'ibexa_content_field_identifier_image_asset',
                $this->getImageAssetContentFieldIdentifier(...),
                ['is_safe' => ['html']]
            ),
        ];
    }

    /**
     * Returns the image variation object for $field/$versionInfo.
     *
     * @param Field $field
     * @param VersionInfo $versionInfo
     * @param string $variationName
     *
     * @return Variation|null
     */
    public function getImageVariation(
        Field $field,
        VersionInfo $versionInfo,
        $variationName
    ) {
        try {
            return $this->imageVariationService->getVariation($field, $versionInfo, $variationName);
        } catch (InvalidVariationException $e) {
            if (isset($this->logger)) {
                $this->logger->error("Couldn't get variation '{$variationName}' for image with id {$field->value->id}");
            }
        } catch (SourceImageNotFoundException $e) {
            if (isset($this->logger)) {
                $this->logger->error(
                    "Couldn't create variation '{$variationName}' for image with id {$field->value->id} because source image can't be found"
                );
            }
        } catch (InvalidArgumentException $e) {
            if (isset($this->logger)) {
                $this->logger->error(
                    "Couldn't create variation '{$variationName}' for image with id {$field->value->id} because an image could not be created from the given input"
                );
            }
        }
    }

    /**
     * Return identifier of the field used to store Image Asset value.
     *
     * Typically used to create generic view of the Image Asset field.
     *
     * @return string
     */
    public function getImageAssetContentFieldIdentifier(): string
    {
        return $this->assetMapper->getContentFieldIdentifier();
    }
}
