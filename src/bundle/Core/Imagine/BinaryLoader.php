<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Bundle\Core\Imagine;

use Exception;
use Ibexa\Core\IO\Exception\InvalidBinaryFileIdException;
use Ibexa\Core\IO\IOServiceInterface;
use Ibexa\Core\IO\Values\MissingBinaryFile;
use Liip\ImagineBundle\Binary\BinaryInterface;
use Liip\ImagineBundle\Binary\Loader\LoaderInterface;
use Liip\ImagineBundle\Exception\Binary\Loader\NotLoadableException;
use Liip\ImagineBundle\Model\Binary;
use Symfony\Component\Mime\MimeTypesInterface;

/**
 * Binary loader using Ibexa IOService.
 * To be used by LiipImagineBundle.
 */
class BinaryLoader implements LoaderInterface
{
    private IOServiceInterface $ioService;

    private MimeTypesInterface $mimeTypes;

    public function __construct(IOServiceInterface $ioService, MimeTypesInterface $mimeTypes)
    {
        $this->ioService = $ioService;
        $this->mimeTypes = $mimeTypes;
    }

    /**
     * @param string $path
     */
    public function find($path): BinaryInterface
    {
        try {
            $binaryFile = $this->ioService->loadBinaryFile($path);
            // Treat a MissingBinaryFile as a not loadable file.
            if ($binaryFile instanceof MissingBinaryFile) {
                throw new NotLoadableException("Source image not found in $path");
            }

            $mimeType = $this->ioService->getMimeType($path);

            return new Binary(
                $this->ioService->getFileContents($binaryFile),
                $mimeType ?? '',
                $this->mimeTypes->getExtensions($mimeType ?? '')[0] ?? null
            );
        } catch (InvalidBinaryFileIdException $e) {
            $message =
                "Source image not found in $path. Repository images path are expected to be " .
                'relative to the var/<site>/storage/images directory';

            $suggestedPath = preg_replace('#var/[^/]+/storage/images/#', '', $path);
            if ($suggestedPath !== $path) {
                $message .= "\nSuggested value: '$suggestedPath'";
            }

            throw new NotLoadableException($message);
        } catch (Exception $e) {
            throw new NotLoadableException("Source image not found in $path", 0, $e);
        }
    }
}
