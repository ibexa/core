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
 * @property-read array $fieldSettings @deprecated 4.6.7 accessing magic getter is deprecated and will be removed in 5.0.0. Use {@see FieldDefinition::getFieldSettings()} instead.
 * @property-read array $validatorConfiguration @deprecated 4.6.7 accessing magic getter is deprecated and will be removed in 5.0.0. Use {@see FieldDefinition::getValidatorConfiguration()} instead.
 * @property-read int $id @deprecated 4.6.7 accessing magic getter is deprecated and will be removed in 5.0.0. Use {@see FieldDefinition::getId()} instead.
 * @property-read string $identifier @deprecated 4.6.7 accessing magic getter is deprecated and will be removed in 5.0.0. Use {@see FieldDefinition::getIdentifier()} instead.
 * @property-read string $fieldGroup @deprecated 4.6.7 accessing magic getter is deprecated and will be removed in 5.0.0. Use {@see FieldDefinition::getFieldGroup()} instead.
 * @property-read int $position @deprecated 4.6.7 accessing magic getter is deprecated and will be removed in 5.0.0. Use {@see FieldDefinition::getPosition()} instead.
 * @property-read string $fieldTypeIdentifier @deprecated 4.6.7 accessing magic getter is deprecated and will be removed in 5.0.0. Use {@see FieldDefinition::getFieldTypeIdentifier()} instead.
 * @property-read bool $isTranslatable @deprecated 4.6.7 accessing magic getter is deprecated and will be removed in 5.0.0. Use {@see FieldDefinition::$isTranslatable()} instead.
 * @property-read bool $isRequired @deprecated 4.6.7 accessing magic getter is deprecated and will be removed in 5.0.0. Use {@see FieldDefinition::$isRequired()} instead.
 * @property-read bool $isSearchable @deprecated 4.6.7 accessing magic getter is deprecated and will be removed in 5.0.0. Use {@see FieldDefinition::getIdentifier()} instead.
 * @property-read bool $isThumbnail @deprecated 4.6.7 accessing magic getter is deprecated and will be removed in 5.0.0. Use {@see FieldDefinition::isThumbnail()} instead.
 * @property-read bool $isInfoCollector @deprecated 4.6.7 accessing magic getter is deprecated and will be removed in 5.0.0. Use {@see FieldDefinition::$isInfoCollector()} instead.
 * @property-read mixed $defaultValue @deprecated 4.6.7 accessing magic getter is deprecated and will be removed in 5.0.0. Use {@see FieldDefinition::getDefaultValue()} instead.
 * @property-read string $mainLanguageCode @deprecated 4.6.7 accessing magic getter is deprecated and will be removed in 5.0.0. Use {@see FieldDefinition::getMainLanguageCode()} instead.
 */
abstract class FieldDefinition extends ValueObject implements MultiLanguageName, MultiLanguageDescription
{
    /**
     * the unique id of this field definition.
     */
    protected int $id;

    /**
     * Readable string identifier of a field definition.
     */
    protected string $identifier;

    /**
     * Field group name.
     */
    protected string $fieldGroup;

    /**
     * the position of the field definition in the content type.
     */
    protected int $position;

    /**
     * String identifier of the field type.
     */
    protected string $fieldTypeIdentifier;

    /**
     * If the field is translatable.
     */
    protected bool $isTranslatable = true;

    /**
     * Indicates if the field can be a thumbnail.
     */
    protected bool $isThumbnail = false;

    /**
     * Is the field required.
     */
    protected bool $isRequired = false;

    /**
     * the flag if this field is used for information collection.
     */
    protected bool $isInfoCollector = false;

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
     */
    protected mixed $defaultValue;

    /**
     * Indicates if th the content is searchable by this attribute.
     */
    protected bool $isSearchable = false;

    /**
     * Based on mainLanguageCode of contentType.
     */
    protected string $mainLanguageCode;

    public function getId(): int
    {
        return $this->id;
    }

    public function getFieldGroup(): string
    {
        return $this->fieldGroup;
    }

    public function getPosition(): int
    {
        return $this->position;
    }

    public function isTranslatable(): bool
    {
        return $this->isTranslatable;
    }

    public function isRequired(): bool
    {
        return $this->isRequired;
    }

    public function isInfoCollector(): bool
    {
        return $this->isInfoCollector;
    }

    public function getDefaultValue(): mixed
    {
        return $this->defaultValue;
    }

    public function isSearchable(): bool
    {
        return $this->isSearchable;
    }

    public function getMainLanguageCode(): string
    {
        return $this->mainLanguageCode;
    }

    public function isThumbnail(): bool
    {
        return $this->isThumbnail;
    }

    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    public function getFieldTypeIdentifier(): string
    {
        return $this->fieldTypeIdentifier;
    }
}
