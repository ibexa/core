<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Bundle\Core\Imagine;

use Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException as APIInvalidArgumentException;
use Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException as APINotFoundException;
use Ibexa\Contracts\Core\Repository\Values\Content\Field;
use Ibexa\Contracts\Core\Repository\Values\Content\VersionInfo;
use Ibexa\Contracts\Core\Variation\Values\Variation;
use Ibexa\Contracts\Core\Variation\VariationHandler;
use Ibexa\Core\FieldType\Image\Value as ImageValue;
use Ibexa\Core\FieldType\Value;
use Ibexa\Core\IO\IOServiceInterface;
use InvalidArgumentException;
use Liip\ImagineBundle\Exception\Imagine\Cache\Resolver\NotResolvableException;
use Liip\ImagineBundle\Imagine\Cache\Resolver\ResolverInterface;

class PlaceholderAliasGenerator implements VariationHandler
{
    private VariationHandler $aliasGenerator;

    private ResolverInterface $ioResolver;

    private IOServiceInterface $ioService;

    private ?PlaceholderProvider $placeholderProvider = null;

    /** @var array<string, mixed> */
    private array $placeholderOptions = [];

    private bool $verifyBinaryDataAvailability = false;

    public function __construct(
        VariationHandler $aliasGenerator,
        ResolverInterface $ioResolver,
        IOServiceInterface $ioService
    ) {
        $this->aliasGenerator = $aliasGenerator;
        $this->ioResolver = $ioResolver;
        $this->ioService = $ioService;
    }

    /**
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\UnauthorizedException
     */
    public function getVariation(
        Field $field,
        VersionInfo $versionInfo,
        string $variationName,
        array $parameters = []
    ): Variation {
        if ($this->placeholderProvider !== null) {
            /** @var \Ibexa\Core\FieldType\Image\Value $imageValue */
            $imageValue = $field->value;
            if (!$this->supportsValue($imageValue)) {
                throw new InvalidArgumentException(
                    "Value of Field with ID {$field->id} ($field->fieldDefIdentifier) cannot be used for generating an image placeholder."
                );
            }

            if (!$this->isOriginalImageAvailable($imageValue)) {
                $binary = $this->ioService->newBinaryCreateStructFromLocalFile(
                    $this->placeholderProvider->getPlaceholder($imageValue, $this->placeholderOptions)
                );
                $binary->id = $imageValue->id;

                $this->ioService->createBinaryFile($binary);
            }
        }

        return $this->aliasGenerator->getVariation($field, $versionInfo, $variationName, $parameters);
    }

    /**
     * @param array<string, mixed> $options
     */
    public function setPlaceholderProvider(PlaceholderProvider $provider, array $options = []): void
    {
        $this->placeholderProvider = $provider;
        $this->placeholderOptions = $options;
    }

    /**
     * Enable/disable binary data availability verification.
     *
     * If enabled, then binary data storage will be used to check if original file exists. Required for DFS setup.
     */
    public function setVerifyBinaryDataAvailability(bool $verifyBinaryDataAvailability): void
    {
        $this->verifyBinaryDataAvailability = $verifyBinaryDataAvailability;
    }

    public function supportsValue(Value $value): bool
    {
        return $value instanceof ImageValue;
    }

    private function isOriginalImageAvailable(ImageValue $imageValue): bool
    {
        try {
            $this->ioResolver->resolve($imageValue->id, IORepositoryResolver::VARIATION_ORIGINAL);
        } catch (NotResolvableException) {
            return false;
        }

        if ($this->verifyBinaryDataAvailability) {
            try {
                // Try to open input stream to an original file
                $this->ioService->getFileInputStream($this->ioService->loadBinaryFile($imageValue->id));
            } catch (APINotFoundException | APIInvalidArgumentException) {
                return false;
            }
        }

        return true;
    }
}
