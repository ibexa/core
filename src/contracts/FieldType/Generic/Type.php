<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\Core\FieldType\Generic;

use Ibexa\Contracts\Core\Exception\InvalidArgumentType;
use Ibexa\Contracts\Core\FieldType\FieldType;
use Ibexa\Contracts\Core\FieldType\Generic\ValidationError\ConstraintViolationAdapter;
use Ibexa\Contracts\Core\FieldType\ValidationError\NonConfigurableValidationError;
use Ibexa\Contracts\Core\FieldType\ValidationError\UnknownValidatorValidationError;
use Ibexa\Contracts\Core\FieldType\Value;
use Ibexa\Contracts\Core\FieldType\ValueSerializerInterface;
use Ibexa\Contracts\Core\Persistence\Content\FieldValue as PersistenceValue;
use Ibexa\Contracts\Core\Repository\Values\ContentType\FieldDefinition;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

abstract class Type extends FieldType
{
    /** @var \Ibexa\Contracts\Core\FieldType\ValueSerializerInterface */
    protected $serializer;

    /** @var \Symfony\Component\Validator\Validator\ValidatorInterface */
    protected $validator;

    public function __construct(ValueSerializerInterface $serializer, ValidatorInterface $validator)
    {
        $this->serializer = $serializer;
        $this->validator = $validator;
    }

    public function getName(Value $value, FieldDefinition $fieldDefinition, string $languageCode): string
    {
        return (string)$value;
    }

    public function getEmptyValue(): Value
    {
        $class = $this->getValueClass();

        return new $class();
    }

    public function fromHash($hash): Value
    {
        if ($hash) {
            return $this->serializer->denormalize($hash, $this->getValueClass());
        }

        return $this->getEmptyValue();
    }

    public function toHash(Value $value): ?array
    {
        if ($this->isEmptyValue($value)) {
            return null;
        }

        return $this->serializer->normalize($value);
    }

    /**
     * @see https://symfony.com/doc/current/validation/raw_values.html
     */
    protected function getFieldSettingsConstraints(): ?Assert\Collection
    {
        return null;
    }

    /**
     * @see https://symfony.com/doc/current/validation/raw_values.html
     */
    protected function getFieldValueConstraints(FieldDefinition $fieldDefinition): ?Assert\Collection
    {
        return null;
    }

    protected function mapConstraintViolationList(ConstraintViolationListInterface $constraintViolationList): array
    {
        $errors = [];

        /** @var \Symfony\Component\Validator\ConstraintViolationInterface $constraintViolation */
        foreach ($constraintViolationList as $constraintViolation) {
            $errors[] = new ConstraintViolationAdapter($constraintViolation);
        }

        return $errors;
    }

    public function getSettingsSchema(): array
    {
        return [];
    }

    public function getValidatorConfigurationSchema(): array
    {
        return [];
    }

    public function validate(FieldDefinition $fieldDefinition, Value $value): array
    {
        if ($this->isEmptyValue($value)) {
            return [];
        }

        return $this->mapConstraintViolationList(
            $this->validator->validate($value, $this->getFieldValueConstraints($fieldDefinition))
        );
    }

    public function validateValidatorConfiguration($validatorConfiguration): array
    {
        $validationErrors = [];

        foreach ((array)$validatorConfiguration as $validatorIdentifier => $constraints) {
            $validationErrors[] = new UnknownValidatorValidationError(
                $validatorIdentifier,
                "[$validatorIdentifier]"
            );
        }

        return $validationErrors;
    }

    public function applyDefaultValidatorConfiguration(&$validatorConfiguration): void
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

    public function validateFieldSettings($fieldSettings): array
    {
        if (empty($this->getSettingsSchema()) && !empty($fieldSettings)) {
            return [
                new NonConfigurableValidationError($this->getFieldTypeIdentifier(), 'fieldType'),
            ];
        }

        if (empty($fieldSettings)) {
            return [];
        }

        return $this->mapConstraintViolationList(
            $this->validator->validate($fieldSettings, $this->getFieldSettingsConstraints())
        );
    }

    public function applyDefaultSettings(&$fieldSettings): void
    {
        if ($fieldSettings !== null && !is_array($fieldSettings)) {
            throw new InvalidArgumentType('$fieldSettings', 'array|null', $fieldSettings);
        }

        foreach ($this->getSettingsSchema() as $settingName => $settingConfiguration) {
            // Checking that a default entry exists in the settingsSchema but that no value has been provided
            if (!array_key_exists($settingName, (array)$fieldSettings) && array_key_exists('default', $settingConfiguration)) {
                $fieldSettings[$settingName] = $settingConfiguration['default'];
            }
        }
    }

    /**
     * Returns information for FieldValue->$sortKey relevant to the field type.
     *
     * Return value is mixed. It should be something which is sensible for
     * sorting.
     *
     * It is up to the persistence implementation to handle those values.
     * Common string and integer values are safe.
     *
     * For the legacy storage it is up to the field converters to set this
     * value in either sort_key_string or sort_key_int.
     *
     * In case of multi value, values should be string and separated by "-" or ",".
     *
     * @return mixed
     */
    protected function getSortInfo(Value $value)
    {
        return null;
    }

    public function toPersistenceValue(Value $value): PersistenceValue
    {
        return new PersistenceValue(
            [
                'data' => $this->toHash($value),
                'externalData' => null,
                'sortKey' => $this->getSortInfo($value),
            ]
        );
    }

    public function fromPersistenceValue(PersistenceValue $fieldValue)
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

    public function isEmptyValue(Value $value): bool
    {
        return $value == $this->getEmptyValue();
    }

    final public function acceptValue($inputValue): Value
    {
        if ($inputValue === null) {
            return $this->getEmptyValue();
        }

        $value = $this->createValueFromInput($inputValue);

        $this->checkValueType($value);

        if ($this->isEmptyValue($value)) {
            return $this->getEmptyValue();
        }

        return $value;
    }

    /**
     * Inspects given $inputValue and potentially converts it into a dedicated value object.
     *
     * If given $inputValue could not be converted or is already an instance of dedicate value object,
     * the method should simply return it.
     *
     * This is an operation method for {@see Type::acceptValue()}.
     *
     * Example implementation:
     * ```
     *  protected function createValueFromInput($inputValue)
     *  {
     *      if (is_array($inputValue))
     *      {
     *          $inputValue = \My\FieldType\CookieJar\Value($inputValue);
     *      }
     *
     *      return $inputValue;
     *  }
     * ```
     *
     * @param mixed $inputValue
     *
     * @return mixed The potentially converted input value.
     */
    protected function createValueFromInput($inputValue)
    {
        if (is_string($inputValue)) {
            $inputValue = $this->serializer->denormalize(
                $this->serializer->decode($inputValue),
                $this->getValueClass()
            );
        }

        return $inputValue;
    }

    /**
     * Returns FQN of class representing Field Type Value.
     *
     * @return string
     */
    protected function getValueClass(): string
    {
        return substr_replace(static::class, 'Value', strrpos(static::class, '\\') + 1);
    }

    /**
     * Throws an exception if the given $value is not an instance of the supported value subtype.
     *
     * This is an operation method for {@see Type::acceptValue()}.
     *
     * Default implementation expects the value class to reside in the same namespace as its
     * FieldType class and is named "Value".
     *
     * Example implementation:
     * ```
     *  protected function checkValueType($value): void
     *  {
     *      if (!$inputValue instanceof \My\FieldType\CookieJar\Value))
     *      {
     *          throw new InvalidArgumentException("Given value type isn't supported.");
     *      }
     *  }
     * ```
     *
     * @param mixed $value A value returned by {@see Type::createValueFromInput()}.
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException If the parameter is not an instance of the supported value subtype.
     */
    protected function checkValueType($value): void
    {
        $valueClass = $this->getValueClass();
        if (!$value instanceof $valueClass) {
            throw new InvalidArgumentType('$value', $valueClass, $value);
        }
    }

    public function fieldSettingsToHash($fieldSettings)
    {
        return $fieldSettings;
    }

    public function fieldSettingsFromHash($fieldSettingsHash)
    {
        return $fieldSettingsHash;
    }

    public function validatorConfigurationToHash($validatorConfiguration)
    {
        return $validatorConfiguration;
    }

    public function validatorConfigurationFromHash($validatorConfiguration)
    {
        return $validatorConfiguration;
    }

    public function getRelations(Value $value): array
    {
        return [];
    }
}
