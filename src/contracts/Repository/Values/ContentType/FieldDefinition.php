<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\Core\Repository\Values\ContentType;

use Ibexa\Contracts\Core\Repository\Values\MultiLanguageDescription;
use Ibexa\Contracts\Core\Repository\Values\MultiLanguageName;
use Ibexa\Contracts\Core\Repository\Values\ValueObject;

/**
 * This class represents a field definition.
 *
 * @property-read array $fieldSettings calls getFieldSettings()
 * @property-read array $validatorConfiguration calls getValidatorConfiguration()
 */
abstract class FieldDefinition extends ValueObject implements MultiLanguageName, MultiLanguageDescription
{
    /**
     * the unique id of this field definition.
     *
     * @var int
     */
    protected $id;

    /**
     * Readable string identifier of a field definition.
     *
     * @var string
     */
    protected $identifier;

    /**
     * Field group name.
     *
     * @var string
     */
    protected $fieldGroup;

    /**
     * the position of the field definition in the content type.
     *
     * @var int
     */
    protected $position;

    /**
     * String identifier of the field type.
     *
     * @var string
     */
    protected $fieldTypeIdentifier;

    /**
     * If the field is translatable.
     *
     * @var bool
     */
    protected $isTranslatable;

    /**
     * Indicates if the field can be a thumbnail.
     *
     * @var bool
     */
    protected $isThumbnail;

    /**
     * Is the field required.
     *
     * @var bool
     */
    protected $isRequired;

    /**
     * the flag if this field is used for information collection.
     *
     * @var bool
     */
    protected $isInfoCollector;

    /**
     * This method returns the validator configuration of this field definition supported by the field type.
     *
     * @return array
     */
    abstract public function getValidatorConfiguration(): array;

    /**
     * This method returns settings for the field definition supported by the field type.
     *
     * @return array
     */
    abstract public function getFieldSettings(): array;

    /**
     * Default value of the field.
     *
     * @var mixed
     */
    protected $defaultValue;

    /**
     * Indicates if th the content is searchable by this attribute.
     *
     * @var bool
     */
    protected $isSearchable;

    /**
     * Based on mainLanguageCode of contentType.
     *
     * @var string
     */
    protected $mainLanguageCode;
}

class_alias(FieldDefinition::class, 'eZ\Publish\API\Repository\Values\ContentType\FieldDefinition');
