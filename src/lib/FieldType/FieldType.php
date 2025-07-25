<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\FieldType;

use Ibexa\Contracts\Core\FieldType\Comparable;
use Ibexa\Contracts\Core\FieldType\FieldType as SPIFieldType;
use Ibexa\Contracts\Core\FieldType\Value as SPIValue;
use Ibexa\Contracts\Core\Persistence\Content\FieldValue as PersistenceValue;
use Ibexa\Contracts\Core\Repository\Values\ContentType\FieldDefinition;
use Ibexa\Core\Base\Exceptions\InvalidArgumentType;
use Ibexa\Core\Persistence\TransformationProcessor;

/**
 * Base class for field types, the most basic storage unit of data inside Ibexa.
 *
 * All other field types extend FieldType providing the specific functionality
 * desired in each case.
 *
 * The capabilities supported by each individual field type is decided by which
 * interfaces the field type implements support for. These individual
 * capabilities can also be checked via the supports*() methods.
 *
 * Field types are the base building blocks of content types, and serve as
 * data containers for Content objects. Therefore, while field types can be used
 * independently, they are designed to be used as a part of a Content object.
 *
 * Field types are primed and pre-configured with the Field Definitions found in
 * content types.
 */
abstract class FieldType extends SPIFieldType implements Comparable
{
    /**
     * The setting keys which are available on this field type.
     *
     * The key is the setting name, and the value is the default value for given
     * setting, set to null if no particular default should be set.
     *
     * @var array<string, mixed>
     */
    protected array $settingsSchema = [];

    /**
     * The validator configuration schema.
     *
     * This is a base implementation, containing an empty array() that indicates
     * that no validators are supported. Overwrite in derived types, if
     * validation is supported.
     *
     * @see getValidatorConfigurationSchema()
     *
     * @var array<string, mixed>
     */
    protected array $validatorConfigurationSchema = [];

    /**
     * String transformation processor, used to normalize sort string as needed.
     *
     * @var \Ibexa\Core\Persistence\TransformationProcessor
     */
    protected $transformationProcessor;

    /**
     * @param \Ibexa\Core\Persistence\TransformationProcessor $transformationProcessor
     */
    public function setTransformationProcessor(TransformationProcessor $transformationProcessor)
    {
        $this->transformationProcessor = $transformationProcessor;
    }

    /**
     * Returns a schema for the settings expected by the FieldType.
     *
     * This implementation returns an array.
     * where the key is the setting name, and the value is the default value for given
     * setting and set to null if no particular default should be set.
     */
    public function getSettingsSchema(): array
    {
        return $this->settingsSchema;
    }

    /**
     * Returns a schema for the validator configuration expected by the FieldType.
     *
     * @see \Ibexa\Contracts\Core\FieldType\FieldType::getValidatorConfigurationSchema()
     *
     * This implementation returns a three-dimensional map containing for each validator configuration
     * referenced by identifier a map of supported parameters which are defined by a type and a default value
     * (see example).
     *
     * ```
     *  [
     *      'stringLength' => [
     *          'minStringLength' => [
     *              'type'    => 'int',
     *              'default' => 0,
     *          ],
     *          'maxStringLength' => [
     *              'type'    => 'int'
     *              'default' => null,
     *          ]
     *      ],
     *  ];
     * ```
     */
    public function getValidatorConfigurationSchema(): array
    {
        return $this->validatorConfigurationSchema;
    }

    /**
     * Validates a field based on the validators in the field definition.
     *
     * This is a base implementation, returning an empty array() that indicates
     * that no validation errors occurred. Overwrite in derived types, if
     * validation is supported.
     *
     * @param \Ibexa\Contracts\Core\Repository\Values\ContentType\FieldDefinition $fieldDefinition The field definition of the field
     * @param \Ibexa\Core\FieldType\Value $value The field value for which an action is performed
     *
     * @return \Ibexa\Contracts\Core\FieldType\ValidationError[]
     *
     *@throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException
     */
    public function validate(FieldDefinition $fieldDefinition, SPIValue $value): array
    {
        return [];
    }

    /**
     * Validates the validatorConfiguration of a FieldDefinitionCreateStruct or FieldDefinitionUpdateStruct.
     *
     * This method expects that given $validatorConfiguration is complete, for this purpose method
     * {@link self::applyDefaultValidatorConfiguration()} is provided.
     *
     * This is a base implementation, returning a validation error for each
     * specified validator, since by default no validators are supported.
     * Overwrite in derived types, if validation is supported.
     *
     * @param mixed $validatorConfiguration
     *
     * @return \Ibexa\Contracts\Core\FieldType\ValidationError[]
     */
    public function validateValidatorConfiguration(mixed $validatorConfiguration): array
    {
        $validationErrors = [];

        foreach ((array)$validatorConfiguration as $validatorIdentifier => $constraints) {
            $validationErrors[] = new ValidationError(
                "Validator '%validator%' is unknown",
                null,
                [
                    'validator' => $validatorIdentifier,
                ],
                "[$validatorIdentifier]"
            );
        }

        return $validationErrors;
    }

    /**
     * Applies the default values to the given $validatorConfiguration of a FieldDefinitionCreateStruct.
     *
     * This is a base implementation, expecting best practice validator configuration format used by
     * field types in standard Ibexa installation. Overwrite in derived types if needed.
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException
     *
     * @param mixed $validatorConfiguration
     */
    public function applyDefaultValidatorConfiguration(mixed &$validatorConfiguration): void
    {
        if ($validatorConfiguration !== null && !is_array($validatorConfiguration)) {
            throw new InvalidArgumentType('$validatorConfiguration', 'array|null', $validatorConfiguration);
        }

        foreach ($this->getValidatorConfigurationSchema() as $validatorName => $configurationSchema) {
            // Set configuration of specific validator to empty array if it is not already provided
            if (!isset($validatorConfiguration[$validatorName])) {
                $validatorConfiguration[$validatorName] = [];
            }

            foreach ($configurationSchema as $settingName => $settingConfiguration) {
                // Check that a default entry exists in the configuration schema for the validator but that no value has been provided
                if (!isset($validatorConfiguration[$validatorName][$settingName]) && array_key_exists('default', $settingConfiguration)) {
                    $validatorConfiguration[$validatorName][$settingName] = $settingConfiguration['default'];
                }
            }
        }
    }

    /**
     * Validates the fieldSettings of a FieldDefinitionCreateStruct or FieldDefinitionUpdateStruct.
     *
     * This method expects that given $fieldSettings are complete, for this purpose method
     * {@link self::applyDefaultSettings()} is provided.
     */
    public function validateFieldSettings(array $fieldSettings): array
    {
        if (!empty($fieldSettings)) {
            return [
                new ValidationError(
                    "FieldType '%fieldType%' does not accept settings",
                    null,
                    [
                        'fieldType' => $this->getFieldTypeIdentifier(),
                    ],
                    'fieldType'
                ),
            ];
        }

        return [];
    }

    /**
     * {@inheritDoc}
     *
     * This is a base implementation, expecting best practice field settings format used by
     * field types in standard Ibexa installation. Overwrite in derived types if needed.
     */
    public function applyDefaultSettings(array &$fieldSettings): void
    {
        foreach ($this->getSettingsSchema() as $settingName => $settingConfiguration) {
            // Checking that a default entry exists in the settingsSchema but that no value has been provided
            if (!array_key_exists($settingName, $fieldSettings) && array_key_exists('default', $settingConfiguration)) {
                $fieldSettings[$settingName] = $settingConfiguration['default'];
            }
        }
    }

    /**
     * Returns information for FieldValue->$sortKey relevant to the field type.
     *
     * The return value is mixed. It should be a scalar that is sensible for sorting.
     *
     * It is up to the persistence implementation to handle those values.
     * Common string and integer values are safe.
     *
     * For the legacy storage, it is up to the field converters to set this
     * value in either sort_key_string or sort_key_int.
     *
     * In the case of multi-value, values should be string and separated by "-" or ",".
     */
    protected function getSortInfo(SPIValue $value): mixed
    {
        return null;
    }

    public function toPersistenceValue(SPIValue $value): PersistenceValue
    {
        // @todo Evaluate if creating the sortKey in every case is really needed
        //       Couldn't this be retrieved with a method, which would initialize
        //       that info on request only?
        return new PersistenceValue(
            [
                'data' => $this->toHash($value),
                'externalData' => null,
                'sortKey' => $this->getSortInfo($value),
            ]
        );
    }

    public function fromPersistenceValue(PersistenceValue $fieldValue): SPIValue
    {
        return $this->fromHash($fieldValue->data);
    }

    public function isSearchable(): bool
    {
        return false;
    }

    public function isSingular(): bool
    {
        return false;
    }

    public function onlyEmptyInstance(): bool
    {
        return false;
    }

    public function isEmptyValue(SPIValue $value): bool
    {
        return $value == $this->getEmptyValue();
    }

    final public function acceptValue(mixed $inputValue): SPIValue
    {
        if ($inputValue === null) {
            return $this->getEmptyValue();
        }

        $value = $this->createValueFromInput($inputValue);

        static::checkValueType($value);

        if ($this->isEmptyValue($value)) {
            return $this->getEmptyValue();
        }

        $this->checkValueStructure($value);

        return $value;
    }

    /**
     * Inspects given $inputValue and potentially converts it into a dedicated value object.
     *
     * If given $inputValue could not be converted or is already an instance of dedicate value object,
     * the method should simply return it.
     *
     * This is an operation method for {@see acceptValue()}.
     *
     * Example implementation:
     * <code>
     *  protected function createValueFromInput( $inputValue )
     *  {
     *      if ( is_array( $inputValue ) )
     *      {
     *          $inputValue = \My\FieldType\CookieJar\Value( $inputValue );
     *      }
     *
     *      return $inputValue;
     *  }
     * </code>
     *
     * @param mixed $inputValue
     *
     * @return mixed The potentially converted input value.
     */
    abstract protected function createValueFromInput($inputValue);

    /**
     * Throws an exception if the given $value is not an instance of the supported value subtype.
     *
     * This is an operation method for {@see acceptValue()}.
     *
     * Default implementation expects the value class to reside in the same namespace as its
     * FieldType class and is named "Value".
     *
     * Example implementation:
     * <code>
     *  static protected function checkValueType( $value )
     *  {
     *      if ( !$inputValue instanceof \My\FieldType\CookieJar\Value ) )
     *      {
     *          throw new InvalidArgumentException( "Given value type is not supported." );
     *      }
     *  }
     * </code>
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException If the parameter is not an instance of the supported value subtype.
     *
     * @param mixed $value A value returned by {@see createValueFromInput()}.
     */
    protected static function checkValueType($value)
    {
        $fieldTypeFQN = static::class;
        $valueFQN = substr_replace($fieldTypeFQN, 'Value', strrpos($fieldTypeFQN, '\\') + 1);

        if (!$value instanceof $valueFQN) {
            throw new InvalidArgumentType('$value', $valueFQN, $value);
        }
    }

    /**
     * Throws an exception if value structure is not of expected format.
     *
     * Note that this does not include validation after the rules
     * from validators, but only plausibility checks for the general data
     * format.
     *
     * This is an operation method for {@see acceptValue()}.
     *
     * Example implementation:
     * <code>
     *  protected function checkValueStructure( Value $value )
     *  {
     *      if ( !is_array( $value->cookies ) )
     *      {
     *          throw new InvalidArgumentException( "An array of assorted cookies was expected." );
     *      }
     *  }
     * </code>
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException If the value does not match the expected structure.
     *
     * @param \Ibexa\Core\FieldType\Value $value
     */
    abstract protected function checkValueStructure(Value $value);

    /**
     * Converts the given $fieldSettings to a simple hash format.
     *
     * This is the default implementation, which just returns the given
     * $fieldSettings, assuming they are already in a hash format. Overwrite
     * this in your specific implementation, if necessary.
     *
     * @param mixed $fieldSettings
     *
     * @return mixed
     */
    public function fieldSettingsToHash(mixed $fieldSettings): mixed
    {
        return $fieldSettings;
    }

    /**
     * Converts the given $fieldSettingsHash to field settings of the type.
     *
     * This is the reverse operation of {@link fieldSettingsToHash()}.
     *
     * This is the default implementation, which just returns the given
     * $fieldSettingsHash, assuming the supported field settings are already in
     * a hash format. Overwrite this in your specific implementation, if
     * necessary.
     *
     * @param mixed $fieldSettingsHash
     *
     * @return mixed
     */
    public function fieldSettingsFromHash(mixed $fieldSettingsHash): mixed
    {
        return $fieldSettingsHash;
    }

    /**
     * Converts the given $validatorConfiguration to a simple hash format.
     *
     * Default implementation, which just returns the given
     * $validatorConfiguration, which is by convention an array for all
     * internal field types. Overwrite this method, if necessary.
     *
     * @param mixed $validatorConfiguration
     *
     * @return mixed
     */
    public function validatorConfigurationToHash(mixed $validatorConfiguration): mixed
    {
        return $validatorConfiguration;
    }

    /**
     * Converts the given $validatorConfigurationHash to a validator
     * configuration of the type.
     *
     * Default implementation, which just returns the given
     * $validatorConfigurationHash, since the validator configuration is by
     * convention an array for all internal field types. Overwrite this method,
     * if necessary.
     *
     * @param mixed $validatorConfigurationHash
     *
     * @return mixed
     */
    public function validatorConfigurationFromHash(mixed $validatorConfigurationHash): mixed
    {
        return $validatorConfigurationHash;
    }

    public function getRelations(SPIValue $fieldValue): array
    {
        return [];
    }

    public function valuesEqual(SPIValue $value1, SPIValue $value2): bool
    {
        return $this->toHash($value1) === $this->toHash($value2);
    }
}
