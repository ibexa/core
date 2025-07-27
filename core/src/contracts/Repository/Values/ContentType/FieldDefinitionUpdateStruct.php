<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\Core\Repository\Values\ContentType;

use Ibexa\Contracts\Core\Repository\Values\ValueObject;

/**
 * this class is used to update a field definition.
 */
class FieldDefinitionUpdateStruct extends ValueObject
{
    /**
     * If set the identifier of a field definition is changed to this value.
     *
     * Needs to be unique within the context of the content type this is added to.
     */
    public ?string $identifier = null;

    /**
     * If set this array of names with languageCode keys replace the complete name collection.
     *
     * @var array<string, string>|null
     */
    public ?array $names = null;

    /**
     * If set this array of descriptions with languageCode keys replace the complete description collection.
     *
     * @var array<string, mixed>|null
     */
    public ?array $descriptions = null;

    /**
     * If set the field group is changed to this name.
     */
    public ?string $fieldGroup = null;

    /**
     * If set the position of the field in the content type.
     */
    public ?int $position = null;

    /**
     * If set translatable flag is set to this value.
     */
    public ?bool $isTranslatable = null;

    /**
     * If set the required flag is set to this value.
     */
    public ?bool $isRequired = null;

    /**
     * Indicates if the field can be a thumbnail.
     */
    public ?bool $isThumbnail = null;

    /**
     * If set the information collector flag is set to this value.
     */
    public ?bool $isInfoCollector = null;

    /**
     * If set this validator configuration supported by the field type replaces the existing one.
     *
     * @var array<string, mixed>|null
     */
    public ?array $validatorConfiguration = null;

    /**
     * If set this settings supported by the field type replaces the existing ones.
     *
     * @var array<string, mixed>|null
     */
    public ?array $fieldSettings = null;

    /**
     * If set the default value for this field is changed to the given value.
     */
    public mixed $defaultValue = null;

    /**
     * If set the searchable flag is set to this value.
     */
    public ?bool $isSearchable = null;
}
