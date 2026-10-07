<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Bundle\IO\Migration\FileLister;

use Ibexa\Bundle\Core\Imagine\VariationPurger\ImageFileList;
use Ibexa\Bundle\IO\ApiLoader\HandlerRegistry;
use Ibexa\Bundle\IO\Migration\FileListerInterface;
use Ibexa\Bundle\IO\Migration\MigrationHandler;
use Ibexa\Contracts\Core\Variation\VariationPathGenerator;
use Ibexa\Core\IO\Exception\BinaryFileNotFoundException;
use Iterator;
use Liip\ImagineBundle\Imagine\Filter\FilterConfiguration;
use LimitIterator;
use Psr\Log\LoggerInterface;

class ImageFileLister extends MigrationHandler implements FileListerInterface
{
    /** @var ImageFileList */
    private $imageFileList;

    /** @var VariationPathGenerator */
    private $variationPathGenerator;

    /** @var FilterConfiguration */
    private $filterConfiguration;

    /** @var string Directory where images are stored, within the storage dir. Example: 'images' */
    private $imagesDir;

    /**
     * @param HandlerRegistry $metadataHandlerRegistry
     * @param HandlerRegistry $binarydataHandlerRegistry
     * @param LoggerInterface $logger
     * @param Iterator $imageFileList
     * @param VariationPathGenerator
     * @param FilterConfiguration
     * @param string $imagesDir Directory where images are stored, within the storage dir. Example: 'images'
     */
    public function __construct(
        HandlerRegistry $metadataHandlerRegistry,
        HandlerRegistry $binarydataHandlerRegistry,
        Iterator $imageFileList,
        VariationPathGenerator $variationPathGenerator,
        FilterConfiguration $filterConfiguration,
        $imagesDir,
        ?LoggerInterface $logger = null
    ) {
        $this->imageFileList = $imageFileList;
        $this->variationPathGenerator = $variationPathGenerator;
        $this->filterConfiguration = $filterConfiguration;
        $this->imagesDir = $imagesDir;

        $this->imageFileList->rewind();

        parent::__construct($metadataHandlerRegistry, $binarydataHandlerRegistry, $logger);
    }

    public function countFiles()
    {
        return count($this->imageFileList);
    }

    public function loadMetadataList(
        $limit = null,
        $offset = null
    ) {
        $metadataList = [];
        $imageLimitList = new LimitIterator($this->imageFileList, $offset, $limit);
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

class_alias(ImageFileLister::class, 'eZ\Bundle\EzPublishIOBundle\Migration\FileLister\ImageFileLister');
