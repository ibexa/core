<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Bundle\IO\Migration\FileLister;

use Ibexa\Bundle\Core\Imagine\VariationPurger\ImageFileList;
use Ibexa\Bundle\IO\ApiLoader\HandlerRegistry;
use Ibexa\Bundle\IO\Migration\FileListerInterface;
use Ibexa\Bundle\IO\Migration\MigrationHandler;
use Ibexa\Contracts\Core\Variation\VariationPathGenerator;
use Ibexa\Core\IO\Exception\BinaryFileNotFoundException;
use Liip\ImagineBundle\Imagine\Filter\FilterConfiguration;
use LimitIterator;
use Psr\Log\LoggerInterface;

class ImageFileLister extends MigrationHandler implements FileListerInterface
{
    private ImageFileList $imageFileList;

    private VariationPathGenerator $variationPathGenerator;

    private FilterConfiguration $filterConfiguration;

    private string $imagesDir;

    /**
     * @param \Ibexa\Bundle\IO\ApiLoader\HandlerRegistry<\Ibexa\Core\IO\IOMetadataHandler> $metadataHandlerRegistry
     * @param \Ibexa\Bundle\IO\ApiLoader\HandlerRegistry<\Ibexa\Core\IO\IOBinarydataHandler> $binarydataHandlerRegistry
     * @param string $imagesDir Directory where images are stored, within the storage dir. Example: 'images'
     */
    public function __construct(
        HandlerRegistry $metadataHandlerRegistry,
        HandlerRegistry $binarydataHandlerRegistry,
        ImageFileList $imageFileList,
        VariationPathGenerator $variationPathGenerator,
        FilterConfiguration $filterConfiguration,
        string $imagesDir,
        ?LoggerInterface $logger = null,
    ) {
        $this->imageFileList = $imageFileList;
        $this->variationPathGenerator = $variationPathGenerator;
        $this->filterConfiguration = $filterConfiguration;
        $this->imagesDir = $imagesDir;

        $this->imageFileList->rewind();

        parent::__construct($metadataHandlerRegistry, $binarydataHandlerRegistry, $logger);
    }

    public function countFiles(): int
    {
        return count($this->imageFileList);
    }

    public function loadMetadataList(?int $limit = null, ?int $offset = null): array
    {
        $metadataList = [];
        $imageLimitList = new LimitIterator($this->imageFileList, $offset ?? 0, $limit ?? -1);
        $aliasNames = array_keys($this->filterConfiguration->all());

        foreach ($imageLimitList as $originalImageId) {
            try {
                $metadataList[] = $this->fromMetadataHandler->load($this->imagesDir . '/' . $originalImageId);
            } catch (BinaryFileNotFoundException $e) {
                $this->logMissingFile($originalImageId);

                continue;
            }

            foreach ($aliasNames as $aliasName) {
                $variationImageId = $this->variationPathGenerator->getVariationPath($originalImageId, $aliasName);

                try {
                    $metadataList[] = $this->fromMetadataHandler->load($this->imagesDir . '/' . $variationImageId);
                } catch (BinaryFileNotFoundException $e) {
                    $this->logMissingFile($variationImageId);
                }
            }
        }

        return $metadataList;
    }
}
