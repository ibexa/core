<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\FieldType\Image;

use Ibexa\Core\Base\Exceptions\InvalidArgumentException;
use Ibexa\Core\Base\Exceptions\InvalidArgumentType;
use Ibexa\Core\FieldType\Value as BaseValue;

class Value extends BaseValue
{
    /**
     * Image binary file string id.
     *
     * Required.
     */
    public ?string $id = null;

    /**
     * The alternative image text (for example, "Picture of an apple.").
     */
    public ?string $alternativeText = null;

    /**
     * Display file name of the image.
     *
     * Required.
     */
    public ?string $fileName = null;

    /**
     * Size of the image file.
     *
     * Required.
     *
     * @var int|null
     */
    public ?int $fileSize = null;

    /**
     * The image's HTTP URI.
     *
     * @var string|null
     */
    public ?string $uri = null;

    /**
     * External image ID (required by REST for now, see https://issues.ibexa.co/browse/EZP-20831).
     */
    public ?string $imageId = null;

    /**
     * Input image file URI.
     */
    public ?string $inputUri = null;

    /**
     * Original image width.
     */
    public ?int $width = null;

    /**
     * Original image height.
     *
     * @var int|null
     */
    public ?int $height = null;

    /** @var string[] */
    public array $additionalData = [];

    public ?string $mime = null;

    /**
     * @param array{
     *     id?: string|null,
     *     alternativeText?: string|null,
     *     fileName?: string|null,
     *     fileSize?: int|null,
     *     uri?: string|null,
     *     imageId?: string|null,
     *     inputUri?: string|null,
     *     width?: int|null,
     *     height?: int|null,
     *     additionalData?: string[],
     *     mime?: string|null
     * } $imageData
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException if input data contains unknown property
     */
    public function __construct(array $imageData = [])
    {
        foreach ($imageData as $key => $value) {
            if (!property_exists($this, $key)) {
                throw new InvalidArgumentType(
                    sprintf('Image\Value::$%s', $key),
                    'Existing property',
                    $value
                );
            }
        }

        parent::__construct($imageData);
    }

    public function isAlternativeTextEmpty(): bool
    {
        return $this->alternativeText === null || trim($this->alternativeText) === '';
    }

    /**
     * Creates a value only from a file path.
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException
     */
    public static function fromString(string $path): self
    {
        if (!file_exists($path)) {
            throw new InvalidArgumentType(
                '$path',
                'existing file',
                $path
            );
        }

        $filesize = filesize($path);
        if (false === $filesize) {
            throw new InvalidArgumentException('$path', "Failed to get file size of '$path' file");
        }

        return new self(
            [
                'inputUri' => $path,
                'fileName' => basename($path),
                'fileSize' => $filesize,
            ]
        );
    }

    /**
     * Returns the image file size in byte.
     */
    public function getFileSize(): ?int
    {
        return $this->fileSize;
    }

    public function __toString(): string
    {
        return (string)$this->fileName;
    }
}
