<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Bundle\Core\Imagine;

use Ibexa\Bundle\Core\Variation\PathResolver;
use Ibexa\Contracts\Core\Variation\VariationPathGenerator;
use Ibexa\Contracts\Core\Variation\VariationPurger;
use Ibexa\Core\Base\Exceptions\InvalidArgumentValue;
use Ibexa\Core\Base\Exceptions\NotFoundException;
use Ibexa\Core\IO\IOServiceInterface;
use Ibexa\Core\IO\Values\MissingBinaryFile;
use Liip\ImagineBundle\Binary\BinaryInterface;
use Liip\ImagineBundle\Exception\Imagine\Cache\Resolver\NotResolvableException;
use Liip\ImagineBundle\Imagine\Cache\Resolver\ResolverInterface;
use Liip\ImagineBundle\Imagine\Filter\FilterConfiguration;
use Symfony\Component\Routing\RequestContext;

/**
 * LiipImagineBundle cache resolver using Ibexa IO repository.
 */
class IORepositoryResolver extends PathResolver implements ResolverInterface
{
    public const string VARIATION_ORIGINAL = 'original';

    public function __construct(
        private readonly IOServiceInterface $ioService,
        RequestContext $requestContext,
        private readonly FilterConfiguration $filterConfiguration,
        private readonly VariationPurger $variationPurger,
        VariationPathGenerator $variationPathGenerator
    ) {
        parent::__construct($requestContext, $variationPathGenerator);
    }

    public function isStored($path, $filter): bool
    {
        return $this->ioService->exists($this->getFilePath($path, $filter));
    }

    public function resolve($path, $filter): string
    {
        try {
            $binaryFile = $this->ioService->loadBinaryFile($path);

            // Treat a MissingBinaryFile as a not loadable file.
            if ($binaryFile instanceof MissingBinaryFile) {
                throw new NotResolvableException("Variation image not found in $path");
            }

            if ($filter !== static::VARIATION_ORIGINAL) {
                $variationPath = $this->getFilePath($path, $filter);
                $variationBinaryFile = $this->ioService->loadBinaryFile($variationPath);
                $path = $variationBinaryFile->uri;
            } else {
                $path = $binaryFile->uri;
            }

            return sprintf(
                '%s%s',
                $path[0] === '/' ? $this->getBaseUrl() : '',
                $path
            );
        } catch (NotFoundException $e) {
            throw new NotResolvableException("Variation image not found in $path", 0, $e);
        }
    }

    /**
     * Stores image alias in the IO Repository.
     * A temporary file is created to dump the filtered image and is used as basis for creation in the IO Repository.
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException
     */
    public function store(BinaryInterface $binary, $path, $filter): void
    {
        $tmpFile = tmpfile();
        fwrite($tmpFile, $binary->getContent());
        $tmpMetadata = stream_get_meta_data($tmpFile);

        if (!isset($tmpMetadata['uri'])) {
            throw new InvalidArgumentValue('uri', '', BinaryInterface::class);
        }

        $binaryCreateStruct = $this->ioService->newBinaryCreateStructFromLocalFile($tmpMetadata['uri']);

        $binaryCreateStruct->id = $this->getFilePath($path, $filter);
        $this->ioService->createBinaryFile($binaryCreateStruct);

        fclose($tmpFile);
    }

    /**
     * @param string[] $paths The paths where the original files are expected to be.
     * @param string[] $filters The imagine filters in effect.
     */
    public function remove(array $paths, array $filters): void
    {
        if (empty($filters)) {
            $filters = array_keys($this->filterConfiguration->all());
        }

        if (empty($paths)) {
            $this->variationPurger->purge($filters);
        }

        foreach ($paths as $path) {
            foreach ($filters as $filter) {
                $filteredImagePath = $this->getFilePath($path, $filter);
                if (!$this->ioService->exists($filteredImagePath)) {
                    continue;
                }

                $binaryFile = $this->ioService->loadBinaryFile($filteredImagePath);
                $this->ioService->deleteBinaryFile($binaryFile);
            }
        }
    }
}
