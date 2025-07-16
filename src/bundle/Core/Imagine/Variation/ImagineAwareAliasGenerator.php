<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Bundle\Core\Imagine\Variation;

use Ibexa\Bundle\Core\Imagine\IORepositoryResolver;
use Ibexa\Contracts\Core\Repository\Values\Content\Field;
use Ibexa\Contracts\Core\Repository\Values\Content\VersionInfo;
use Ibexa\Contracts\Core\SiteAccess\ConfigResolverInterface;
use Ibexa\Contracts\Core\Variation\Values\ImageVariation;
use Ibexa\Contracts\Core\Variation\Values\Variation;
use Ibexa\Contracts\Core\Variation\VariationHandler;
use Ibexa\Contracts\Core\Variation\VariationPathGenerator;
use Ibexa\Core\IO\IOServiceInterface;
use Ibexa\Core\IO\Values\BinaryFile;
use Imagine\Image\ImagineInterface;

/**
 * Alias Generator Decorator which ensures (using Imagine if needed) that ImageVariation has proper
 * dimensions.
 */
class ImagineAwareAliasGenerator implements VariationHandler
{
    private VariationHandler $aliasGenerator;

    private VariationPathGenerator $variationPathGenerator;

    private IOServiceInterface $ioService;

    private ConfigResolverInterface $configResolver;

    private ImagineInterface $imagine;

    public function __construct(
        VariationHandler $aliasGenerator,
        VariationPathGenerator $variationPathGenerator,
        IOServiceInterface $ioService,
        ImagineInterface $imagine
    ) {
        $this->aliasGenerator = $aliasGenerator;
        $this->variationPathGenerator = $variationPathGenerator;
        $this->ioService = $ioService;
        $this->imagine = $imagine;
    }

    public function getVariation(
        Field $field,
        VersionInfo $versionInfo,
        string $variationName,
        array $parameters = []
    ): Variation {
        /** @var \Ibexa\Contracts\Core\Variation\Values\ImageVariation $variation */
        $variation = $this->aliasGenerator->getVariation(
            $field,
            $versionInfo,
            $variationName,
            $parameters
        );

        if (null === $variation->width || null === $variation->height) {
            $variationBinaryFile = $this->getVariationBinaryFile($field->value->id, $variationName);
            $image = $this->imagine->load($this->ioService->getFileContents($variationBinaryFile));
            $dimensions = $image->getSize();

            return new ImageVariation(
                [
                    'name' => $variation->name,
                    'fileName' => $variation->fileName,
                    'dirPath' => $variation->dirPath,
                    'uri' => $variation->uri,
                    'imageId' => $variation->imageId,
                    'width' => $dimensions->getWidth(),
                    'height' => $dimensions->getHeight(),
                    'fileSize' => $variationBinaryFile->size,
                    'mimeType' => $this->ioService->getMimeType($variationBinaryFile->id),
                    'lastModified' => $variationBinaryFile->mtime,
                ]
            );
        }

        return $variation;
    }

    /**
     * Get an image variation filesystem path.
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException
     */
    private function getVariationBinaryFile(string $originalPath, string $variationName): BinaryFile
    {
        if ($variationName !== IORepositoryResolver::VARIATION_ORIGINAL) {
            $variationPath = $this->variationPathGenerator->getVariationPath(
                $originalPath,
                $variationName
            );
        } else {
            $variationPath = $originalPath;
        }

        return $this->ioService->loadBinaryFile($variationPath);
    }
}
