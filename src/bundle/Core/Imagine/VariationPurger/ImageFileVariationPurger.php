<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Bundle\Core\Imagine\VariationPurger;

use Ibexa\Contracts\Core\Variation\VariationPathGenerator;
use Ibexa\Contracts\Core\Variation\VariationPurger;
use Ibexa\Core\IO\IOServiceInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;

/**
 * Purges image aliases based on image files referenced by the Image FieldType.
 *
 * It uses an ImageFileList iterator that lists original images, and the variationPathGenerator + IOService to remove
 * aliases if they exist.
 */
class ImageFileVariationPurger implements VariationPurger, LoggerAwareInterface
{
    use LoggerAwareTrait;

    public function __construct(
        private readonly ImageFileList $imageFileList,
        private readonly IOServiceInterface $ioService,
        private readonly VariationPathGenerator $variationPathGenerator
    ) {}

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
}
