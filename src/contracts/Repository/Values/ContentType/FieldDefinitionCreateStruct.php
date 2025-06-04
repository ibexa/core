<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\Core\Repository\Values\ContentType;

use Ibexa\Contracts\Core\Repository\Values\ValueObject;

/**
 * this class is used to create a field definition.
 */
class FieldDefinitionCreateStruct extends ValueObject
{
    /**
     * String identifier of the field type.
     *
     * Required.
     */
    public ?string $fieldTypeIdentifier = null;

    /**
     * Readable string identifier of a field definition.
     *
     * Needs to be unique within the context of the content type this is added to.
     *
     * Required.
     */
    public ?string $identifier = null;

    /**
     * An array of names with languageCode keys.
     *
     * @var array<string, string> an array of string
     */
    public array $names = [];

    /**
     * An array of descriptions with languageCode keys.
     *
     * @var array<string, string> an array of string
     */
    public array $descriptions = [];

    /**
     * Field group name.
     */
    public ?string $fieldGroup = null;

    /**
     * The position of the field definition in the content type
     * if not set the field is added at the end.
     */
    public ?int $position = null;

    /**
     * Indicates if the field is translatable.
     */
    public bool $isTranslatable = true;

    /**
     * Indicates if the field is required.
     */
    public bool $isRequired = false;

    /**
     * Indicates if the field can be a thumbnail.
     */
    public bool $isThumbnail = false;

    /**
     * Indicates if this attribute is used for information collection.
     */
    public bool $isInfoCollector = false;

    /**
     * The validator configuration supported by the field type.
     *
     * @var array<string, mixed>|null
     */
    public ?array $validatorConfiguration = null;

    /**
     * The settings supported by the field type.
     *
     * @var array<string, mixed>|null
     */
    public ?array $fieldSettings = null;

    /**
     * Default value of the field.
     */
    public mixed $defaultValue = null;

    /**
     * Indicates if th the content is searchable by this attribute.
     */
    public bool $isSearchable = false;
}
