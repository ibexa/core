<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Bundle\Core\Imagine\VariationPurger;

use Ibexa\Contracts\Core\Variation\VariationPathGenerator;
use Ibexa\Contracts\Core\Variation\VariationPurger;
use Ibexa\Core\IO\IOServiceInterface;
use Iterator;

/**
 * Purges image aliases based on image files referenced by the Image FieldType.
 *
 * It uses an ImageFileList iterator that lists original images, and the variationPathGenerator + IOService to remove
 * aliases if they exist.
 */
class ImageFileVariationPurger implements VariationPurger
{
    /** @var ImageFileList */
    private $imageFileList;

    /** @var \Ibexa\Core\IO\IOServiceInterface */
    private $ioService;

    /** @var \Ibexa\Contracts\Core\Variation\VariationPathGenerator */
    private $variationPathGenerator;

    /** @var \Psr\Log\LoggerInterface */
    private $logger;

    public function __construct(Iterator $imageFileList, IOServiceInterface $ioService, VariationPathGenerator $variationPathGenerator)
    {
        $this->imageFileList = $imageFileList;
        $this->ioService = $ioService;
        $this->variationPathGenerator = $variationPathGenerator;
    }

    public function purge(array $aliasNames): void
    {
        foreach ($this->imageFileList as $originalImageId) {
            foreach ($aliasNames as $aliasName) {
                $variationImageId = $this->variationPathGenerator->getVariationPath($originalImageId, $aliasName);
                if (!$this->ioService->exists($variationImageId)) {
                    continue;
                }

                $binaryFile = $this->ioService->loadBinaryFile($variationImageId);
                $this->ioService->deleteBinaryFile($binaryFile);
                if (isset($this->logger)) {
                    $this->logger->info("Purging $aliasName variation $variationImageId for original image $originalImageId");
                }
            }
        }
    }

    /**
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function setLogger($logger)
    {
        $this->logger = $logger;
    }
}
