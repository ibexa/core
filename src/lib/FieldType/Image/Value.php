<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Core\FieldType\Image;

use Ibexa\Contracts\Core\Repository\Exceptions\PropertyNotFoundException;
use Ibexa\Core\Base\Exceptions\InvalidArgumentType;
use Ibexa\Core\FieldType\Value as BaseValue;

class Value extends BaseValue
{
    /**
     * Image id.
     *
     * Required.
     *
     * @var mixed|null
     */
    public $id;

    /**
     * The alternative image text (for example "Picture of an apple.").
     *
     * @var string|null
     */
    public $alternativeText;

    /**
     * Display file name of the image.
     *
     * Required.
     *
     * @var string|null
     */
    public $fileName;

    /**
     * Size of the image file.
     *
     * Required.
     *
     * @var int|null
     */
    public $fileSize;

    /**
     * The image's HTTP URI.
     *
     * @var string|null
     */
    public $uri;

    /**
     * External image ID (required by REST for now, see https://issues.ibexa.co/browse/EZP-20831).
     *
     * @var mixed|null
     */
    public $imageId;

    /**
     * Input image file URI.
     *
     * @var string|null
     */
    public $inputUri;

    /**
     * Original image width.
     *
     * @var int|null
     */
    public $width;

    /**
     * Original image height.
     *
     * @var int|null
     */
    public $height;

    /** @var string[] */
    public $additionalData = [];

    public ?string $mime = null;

    /**
     * Construct a new Value object.
     */
    public function __construct(array $imageData = [])
    {
        foreach ($imageData as $key => $value) {
            try {
                $this->$key = $value;
            } catch (PropertyNotFoundException $e) {
                throw new InvalidArgumentType(
                    sprintf('Image\Value::$%s', $key),
                    'Existing property',
                    $value
                );
            }
        }
    }

    public function isAlternativeTextEmpty(): bool
    {
        return $this->alternativeText === null || trim($this->alternativeText) === '';
    }

    /**
     * Creates a value only from a file path.
     *
     * @throws \Ibexa\Core\Base\Exceptions\InvalidArgumentType
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

        return new static(
            [
                'inputUri' => $path,
                'fileName' => basename($path),
                'fileSize' => filesize($path),
            ]
        );
    }

    /**
     * Returns the image file size in byte.
     *
     * @return int
     */
    public function getFileSize()
    {
        return $this->fileSize;
    }

    public function __toString()
    {
        return (string)$this->fileName;
    }
}
