<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\FieldType\BinaryBase;

use Ibexa\Core\FieldType\Value as BaseValue;

/**
 * Base value for binary field types.
 *
 * @property string $path Used for BC with legacy 5.0 (EZP-20948). Equivalent to $id.
 * @property-read string $id Unique file ID, set by storage. Read only since 5.3 (EZP-22808).
 */
abstract class Value extends BaseValue
{
    /**
     * Unique file ID, set by storage.
     *
     * Since legacy 5.3 this is not used for input, use self::$inputUri instead
     *
     * @var string|null
     */
    protected ?string $id = null;

    /**
     * Input file URI, as a path to a file on a disk.
     *
     * @var string|null
     */
    public ?string $inputUri = null;

    /**
     * Display file name.
     */
    public ?string $fileName = null;

    /**
     * Size of the image file.
     */
    public ?int $fileSize = null;

    /**
     * Mime type of the file.
     */
    public ?string $mimeType = null;

    /**
     * HTTP URI.
     */
    public ?string $uri = null;

    /**
     * @param array{
     *     inputUri?: string|null,
     *     fileName?: string|null,
     *     fileSize?: int|null,
     *     mimeType?: string|null,
     *     uri?: string|null,
     *     id?: string|null,
     *     path?: string|null
     * } $fileData
     */
    public function __construct(array $fileData = [])
    {
        // BC with legacy 5.0 (EZP-20948)
        if (isset($fileData['path'])) {
            $fileData['id'] = $fileData['path'];
            unset($fileData['path']);
        }

        // BC with legacy 5.2 (EZP-22808)
        if (isset($fileData['id']) && file_exists($fileData['id'])) {
            $fileData['inputUri'] = $fileData['id'];
            unset($fileData['id']);
        }

        parent::__construct($fileData);
    }

    /**
     * Returns a string representation of the field value.
     */
    public function __toString(): string
    {
        return (string)$this->uri;
    }

    public function __get($propertyName): mixed
    {
        if ($propertyName === 'path') {
            return $this->inputUri;
        }

        return parent::__get($propertyName);
    }

    public function __set($propertyName, $propertyValue): void
    {
        // BC with 5.0 (EZP-20948)
        if ($propertyName === 'path') {
            $this->inputUri = $propertyValue;
        } elseif ($propertyName === 'id' && file_exists($propertyValue)) { // BC with 5.2 (EZP-22808)
            $this->inputUri = $propertyValue;
        } else {
            parent::__set($propertyName, $propertyValue);
        }
    }

    public function __isset($propertyName): bool
    {
        if ($propertyName === 'path') {
            return true;
        }

        return parent::__isset($propertyName);
    }
}
