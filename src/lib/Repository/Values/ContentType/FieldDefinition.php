<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\Repository\Values\ContentType;

use Ibexa\Contracts\Core\Repository\Values\ContentType\FieldDefinition as APIFieldDefinition;
use Ibexa\Core\Repository\Values\MultiLanguageDescriptionTrait;
use Ibexa\Core\Repository\Values\MultiLanguageNameTrait;

/**
 * This class represents a field definition.
 *
 * @property-read string[] $names calls getNames() or on access getName($language)
 * @property-read string[] $descriptions calls getDescriptions() or on access getDescription($language)
 * @property-read array $fieldSettings calls getFieldSettings()
 * @property-read array $validatorConfiguration calls getValidatorConfiguration()
 * @property-read mixed $id the id of the field definition
 * @property-read string $identifier the identifier of the field definition
 * @property-read string $fieldGroup the field group name
 * @property-read int $position the position of the field definition in the content type
 * @property-read string $fieldTypeIdentifier String identifier of the field type
 * @property-read bool $isTranslatable indicates if fields of this definition are translatable
 * @property-read bool $isRequired indicates if this field is required in the content object
 * @property-read bool $isSearchable indicates if the field is searchable
 * @property-read bool $isInfoCollector indicates if this field is used for information collection
 * @property-read mixed $defaultValue the default value of the field
 *
 * @internal Meant for internal use by Repository, type hint against API object instead.
 */
class FieldDefinition extends APIFieldDefinition
{
    use MultiLanguageNameTrait;
    use MultiLanguageDescriptionTrait;

    /**
     * Main language.
     */
    protected string $mainLanguageCode;

    /**
     * Prioritized languages provided by user when retrieving object using API.
     *
     * @var string[]
     */
    protected array $prioritizedLanguages = [];

    /**
     * Holds collection of settings for the field definition supported by the field type.
     *
     * @var array
     */
    protected $fieldSettings = [];

    /**
     * Holds validator configuration of this field definition supported by the field type.
     *
     * @var array
     */
    protected $validatorConfiguration = [];

    /**
     * This method returns the validator configuration of this field definition supported by the field type.
     *
     * @return array
     */
    public function getValidatorConfiguration(): array
    {
        return $this->validatorConfiguration;
    }

    /**
     * This method returns settings for the field definition supported by the field type.
     *
     * @return array
     */
    public function getFieldSettings(): array
    {
        return $this->fieldSettings;
    }
}
