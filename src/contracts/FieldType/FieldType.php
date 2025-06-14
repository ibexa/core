<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Contracts\Core\FieldType;

use Ibexa\Contracts\Core\Persistence\Content\FieldValue;
use Ibexa\Contracts\Core\Repository\Values\ContentType\FieldDefinition;

/**
 * The field type interface which all field types have to implement.
 *
 *
 * Hashes:
 *
 * The {@see FieldType::toHash()} method in this class is meant to generate a simple
 * representation of a value of this field type. Hash does here not refer to
 * MD5 or similar hashing algorithms, but rather to hash-map (associative array)
 * type representation. This representation must be
 * usable, to transfer the value over plain text encoding formats, like e.g.
 * XML. As a result, the returned "hash" must either be a scalar value, a hash
 * array (associative array) a pure numeric array or a nested combination of
 * these. It must by no means contain objects, resources or cyclic references.
 * The corresponding {@see FieldType::fromHash()} method must convert such a
 * representation back into a value, which is understood by the FieldType.
 */
abstract class FieldType
{
    /**
     * Returns the field type identifier for this field type.
     *
     * This identifier should be globally unique and the implementer of a
     * FieldType must take care for the uniqueness. It is therefore recommended
     * to prefix the field-type identifier by a unique string that identifies
     * the implementer. A good identifier could for example take your companies main
     * domain name as a prefix in reverse order.
     *
     * @return string
     */
    abstract public function getFieldTypeIdentifier();

    /**
     * Returns a human readable string representation from a given value.
     *
     * It will be used to generate content name and url alias if current field
     * is designated to be used in the content name/urlAlias pattern.
     *
     * The used $value can be assumed to be already accepted by {@see FieldType::acceptValue()}.
     *
     * @param \Ibexa\Contracts\Core\FieldType\Value $value
     * @param \Ibexa\Contracts\Core\Repository\Values\ContentType\FieldDefinition $fieldDefinition
     * @param string $languageCode
     *
     * @return string
     */
    abstract public function getName(Value $value, FieldDefinition $fieldDefinition, string $languageCode): string;

    /**
     * Returns a schema for the settings expected by the FieldType.
     *
     * Returns an arbitrary value, representing a schema for the settings of
     * the FieldType.
     *
     * Explanation: There are no possible generic schemas for defining settings
     * input, which is why no schema for the return value of this method is
     * defined. It is up to the implementer to define and document a schema for
     * the return value and document it. In addition, it is necessary that all
     * consumers of this interface (e.g. Public API, REST API, GUIs, ...)
     * provide plugin mechanisms to hook adapters for the specific FieldType
     * into. These adapters then need to be either shipped with the FieldType
     * or need to be implemented by a third party. If there is no adapter
     * available for a specific FieldType, it will not be usable with the
     * consumer.
     *
     * @return mixed
     */
    abstract public function getSettingsSchema();

    /**
     * Returns a schema for the validator configuration expected by the FieldType.
     *
     * Returns an arbitrary value, representing a schema for the validator
     * configuration of the FieldType.
     *
     * Explanation: There are no possible generic schemas for defining settings
     * input, which is why no schema for the return value of this method is
     * defined. It is up to the implementer to define and document a schema for
     * the return value and document it. In addition, it is necessary that all
     * consumers of this interface (e.g. Public API, REST API, GUIs, ...)
     * provide plugin mechanisms to hook adapters for the specific FieldType
     * into. These adapters then need to be either shipped with the FieldType
     * or need to be implemented by a third party. If there is no adapter
     * available for a specific FieldType, it will not be usable with the
     * consumer.
     *
     * Best practice:
     *
     * It is considered best practice to return a hash map, which contains
     * rudimentary settings structures, like e.g. for the "ibexa_string" FieldType
     *
     * ```
     * [
     *     'stringLength' => [
     *         'minStringLength' => [
     *             'type'    => 'int',
     *             'default' => 0,
     *         ],
     *         'maxStringLength' => [
     *             'type'    => 'int'
     *             'default' => null,
     *         ],
     *     ],
     * ];
     * ```
     *
     * @return mixed
     */
    abstract public function getValidatorConfigurationSchema();

    /**
     * Validates a field based on the validator configuration in the field definition.
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException
     *
     * @param \Ibexa\Contracts\Core\Repository\Values\ContentType\FieldDefinition $fieldDef The field definition of the field
     * @param \Ibexa\Contracts\Core\FieldType\Value $value The field value for which an action is performed
     *
     * @return \Ibexa\Contracts\Core\FieldType\ValidationError[]
     */
    abstract public function validate(FieldDefinition $fieldDef, Value $value);

    /**
     * Validates the validatorConfiguration of a FieldDefinitionCreateStruct or FieldDefinitionUpdateStruct.
     *
     * This methods determines if the given $validatorConfiguration is
     * structurally correct and complies to the validator configuration schema
     * returned by {@see FieldType::getValidatorConfigurationSchema()}.
     *
     * @param mixed $validatorConfiguration
     *
     * @return \Ibexa\Contracts\Core\FieldType\ValidationError[]
     */
    abstract public function validateValidatorConfiguration($validatorConfiguration);

    /**
     * Applies the default values to the given $validatorConfiguration of a FieldDefinitionCreateStruct.
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException
     *
     * @param mixed $validatorConfiguration
     */
    abstract public function applyDefaultValidatorConfiguration(&$validatorConfiguration);

    /**
     * Validates the fieldSettings of a FieldDefinitionCreateStruct or FieldDefinitionUpdateStruct.
     *
     * This methods determines if the given $fieldSettings are structurally
     * correct and comply to the settings schema returned by {@see FieldType::getSettingsSchema()}.
     *
     * @param mixed $fieldSettings
     *
     * @return \Ibexa\Contracts\Core\FieldType\ValidationError[]
     */
    abstract public function validateFieldSettings($fieldSettings);

    /**
     * Applies the default values to the fieldSettings of a FieldDefinitionCreateStruct.
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException
     *
     * @param mixed $fieldSettings
     */
    abstract public function applyDefaultSettings(&$fieldSettings);

    /**
     * Indicates if the field type supports indexing and sort keys for searching.
     *
     * @return bool
     */
    abstract public function isSearchable();

    /**
     * Indicates if the field definition of this type can appear only once in the same ContentType.
     *
     * @return bool
     */
    abstract public function isSingular();

    /**
     * Indicates if the field definition of this type can be added to a ContentType with Content instances.
     *
     * @return bool
     */
    abstract public function onlyEmptyInstance();

    /**
     * Returns the empty value for this field type.
     *
     * This value will be used, if no value was provided for a field of this
     * type and no default value was specified in the field definition. It is
     * also used to determine that a user intentionally (or unintentionally) did not
     * set a non-empty value.
     *
     * @return \Ibexa\Contracts\Core\FieldType\Value
     */
    abstract public function getEmptyValue();

    /**
     * Returns if the given $value is considered empty by the field type.
     *
     * Usually, only the value returned by {@see FieldType::getEmptyValue()} is
     * considered empty. The given $value can be safely assumed to have already
     * been processed by {@see FieldType::acceptValue()}.
     *
     * @param \Ibexa\Contracts\Core\FieldType\Value $value
     *
     * @return bool
     */
    abstract public function isEmptyValue(Value $value);

    /**
     * Potentially builds and checks the type and structure of the $inputValue.
     *
     * This method first inspects $inputValue and convert it into a dedicated
     * value object.
     *
     * After that, the value is checked for structural validity.
     * Note that this does not include validation after the rules
     * from validators, but only plausibility checks for the general data
     * format.
     *
     * Note that this method must also cope with the empty value for the field
     * type as e.g. returned by {@see FieldType::getEmptyValue()}.
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException if the parameter is not of the supported value sub type
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException if the value does not match the expected structure
     *
     * @param mixed $inputValue
     *
     * @return \Ibexa\Contracts\Core\FieldType\Value The potentially converted and structurally plausible value.
     */
    abstract public function acceptValue($inputValue);

    /**
     * Converts an $hash to the Value defined by the field type.
     *
     * This is the reverse operation to {@see FieldType::toHash()}. At least the hash
     * format generated by {@see FieldType::toHash()} must be converted in reverse.
     * Additional formats might be supported in the rare case that this is
     * necessary. See the class description for more details on a hash format.
     *
     * @param mixed $hash
     *
     * @return \Ibexa\Contracts\Core\FieldType\Value
     */
    abstract public function fromHash($hash);

    /**
     * Converts the given $value into a plain hash format.
     *
     * Converts the given $value into a plain hash format, which can be used to
     * transfer the value through plain text formats, e.g. XML, which do not
     * support complex structures like objects. See the class level doc block
     * for additional information. See the class description for more details on a hash format.
     *
     * @param \Ibexa\Contracts\Core\FieldType\Value $value
     *
     * @return mixed
     */
    abstract public function toHash(Value $value);

    /**
     * Converts the given $fieldSettings to a simple hash format.
     *
     * See the class description for more details on a hash format.
     *
     * @param mixed $fieldSettings
     *
     * @return array|scalar|null
     */
    abstract public function fieldSettingsToHash($fieldSettings);

    /**
     * Converts the given $fieldSettingsHash to field settings of the type.
     *
     * This is the reverse operation of {@see FieldType::fieldSettingsToHash()}.
     * See the class description for more details on a hash format.
     *
     * @param array|scalar|null $fieldSettingsHash
     *
     * @return mixed
     */
    abstract public function fieldSettingsFromHash($fieldSettingsHash);

    /**
     * Converts the given $validatorConfiguration to a simple hash format.
     *
     * See the class description for more details on a hash format.
     *
     * @param mixed $validatorConfiguration
     *
     * @return array|scalar|null
     */
    abstract public function validatorConfigurationToHash($validatorConfiguration);

    /**
     * Converts the given $validatorConfigurationHash to a validator
     * configuration of the type.
     *
     * See the class description for more details on a hash format.
     *
     * @param array|scalar|null $validatorConfigurationHash
     *
     * @return mixed
     */
    abstract public function validatorConfigurationFromHash($validatorConfigurationHash);

    /**
     * Converts a $value to a persistence value.
     *
     * In this method the field type puts the data which is stored in the field of content in the repository
     * into the property FieldValue::data. The format of $data is a primitive, an array (map) or an object, which
     * is then canonically converted to e.g. json/xml structures by future storage engines without
     * further conversions. For mapping the $data to the legacy database an appropriate Converter
     * (implementing {@see \Ibexa\Core\Persistence\Legacy\Content\FieldValue\Converter}) has implemented for the field
     * type. Note: $data should only hold data which is actually stored in the field. It must not
     * hold data which is stored externally.
     *
     * The $externalData property in the FieldValue is used for storing data externally by the
     * FieldStorage interface method storeFieldData.
     *
     * The FieldValuer::sortKey is build by the field type for using by sort operations.
     *
     * @see \Ibexa\Contracts\Core\Persistence\Content\FieldValue
     *
     * @param \Ibexa\Contracts\Core\FieldType\Value $value The value of the field type
     *
     * @return \Ibexa\Contracts\Core\Persistence\Content\FieldValue the value processed by the storage engine
     */
    abstract public function toPersistenceValue(Value $value);

    /**
     * Converts a persistence $value to a Value.
     *
     * This method builds a field type value from the $data and $externalData properties.
     *
     * @param \Ibexa\Contracts\Core\Persistence\Content\FieldValue $fieldValue
     *
     * @return \Ibexa\Contracts\Core\FieldType\Value
     */
    abstract public function fromPersistenceValue(FieldValue $fieldValue);

    /**
     * Returns relation data extracted from value.
     *
     * Not intended for \Ibexa\Contracts\Core\Repository\Values\Content\Relation::COMMON type relations,
     * there is an API for handling those.
     *
     * @param \Ibexa\Contracts\Core\FieldType\Value $value
     *
     * @return array Hash with relation type as key and array of destination content IDs as value.
     *
     * Example:
     * ```
     * [
     *     \Ibexa\Contracts\Core\Repository\Values\Content\Relation::LINK => [
     *         'contentIds' => [12, 13, 14],
     *         'locationIds' => [24]
     *     ],
     *     \Ibexa\Contracts\Core\Repository\Values\Content\Relation::EMBED => [
     *         'contentIds" => [12],
     *         'locationIds' => [24, 45]
     *     ],
     *     \Ibexa\Contracts\Core\Repository\Values\Content\Relation::FIELD => [12]
     * ]
     * ```
     */
    abstract public function getRelations(Value $value);
}
