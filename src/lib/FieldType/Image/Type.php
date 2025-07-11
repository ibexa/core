<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Core\FieldType\Image;

use Ibexa\Contracts\Core\FieldType\Value as SPIValue;
use Ibexa\Contracts\Core\Persistence\Content\FieldValue;
use Ibexa\Contracts\Core\Repository\Values\ContentType\FieldDefinition;
use Ibexa\Core\Base\Exceptions\InvalidArgumentType;
use Ibexa\Core\FieldType\FieldType;
use Ibexa\Core\FieldType\ValidationError;
use Ibexa\Core\FieldType\Value as BaseValue;
use JMS\TranslationBundle\Model\Message;
use JMS\TranslationBundle\Translation\TranslationContainerInterface;

/**
 * The Image field type.
 */
class Type extends FieldType implements TranslationContainerInterface
{
    protected $validatorConfigurationSchema = [
        'FileSizeValidator' => [
            'maxFileSize' => [
                'type' => 'numeric',
                'default' => null,
            ],
        ],
        'AlternativeTextValidator' => [
            'required' => [
                'type' => 'bool',
                'default' => false,
            ],
        ],
    ];

    /**
     * @var array{
     *     mimeTypes: array{
     *         type: string,
     *         default: array{},
     *     }
     * }
     */
    protected $settingsSchema = [
        'mimeTypes' => [
            'type' => 'choice',
            'default' => [],
        ],
    ];

    /** @var \Ibexa\Core\FieldType\Validator[] */
    private $validators;

    /** @var array<string> */
    private array $mimeTypes;

    /**
     * @param array<\Ibexa\Core\FieldType\Validator> $validators
     * @param array<string> $mimeTypes
     */
    public function __construct(
        array $validators,
        array $mimeTypes = []
    ) {
        $this->validators = $validators;
        $this->mimeTypes = $mimeTypes;
    }

    /**
     * Returns the field type identifier for this field type.
     *
     * @return string
     */
    public function getFieldTypeIdentifier(): string
    {
        return 'ibexa_image';
    }

    /**
     * @param \Ibexa\Core\FieldType\Image\Value $value
     */
    public function getName(SPIValue $value, FieldDefinition $fieldDefinition, string $languageCode): string
    {
        return $value->alternativeText ?? (string)$value->fileName;
    }

    /**
     * Returns the fallback default value of field type when no such default
     * value is provided in the field definition in content types.
     *
     * @return \Ibexa\Core\FieldType\Image\Value
     */
    public function getEmptyValue()
    {
        return new Value();
    }

    /**
     * Inspects given $inputValue and potentially converts it into a dedicated value object.
     *
     * @param string|array|\Ibexa\Core\FieldType\Image\Value $inputValue
     *
     * @return \Ibexa\Core\FieldType\Image\Value The potentially converted and structurally plausible value.
     */
    protected function createValueFromInput($inputValue)
    {
        if (is_string($inputValue)) {
            $inputValue = Value::fromString($inputValue);
        }

        if (is_array($inputValue)) {
            if (isset($inputValue['inputUri']) && file_exists($inputValue['inputUri'])) {
                $inputValue['fileSize'] = filesize($inputValue['inputUri']);
                if (!isset($inputValue['fileName'])) {
                    $inputValue['fileName'] = basename($inputValue['inputUri']);
                }
            }

            $inputValue = new Value($inputValue);
        }

        return $inputValue;
    }

    /**
     * Throws an exception if value structure is not of expected format.
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException If the value does not match the expected structure.
     *
     * @param \Ibexa\Core\FieldType\Image\Value $value
     */
    protected function checkValueStructure(BaseValue $value)
    {
        if (isset($value->inputUri) && !is_string($value->inputUri)) {
            throw new InvalidArgumentType('$value->inputUri', 'string', $value->inputUri);
        }

        if (isset($value->id) && !is_string($value->id)) {
            throw new InvalidArgumentType('$value->id', 'string', $value->id);
        }

        // Required parameter $fileName
        if (!isset($value->fileName) || !is_string($value->fileName)) {
            throw new InvalidArgumentType('$value->fileName', 'string', $value->fileName);
        }

        // Optional parameter $alternativeText
        if (isset($value->alternativeText) && !is_string($value->alternativeText)) {
            throw new InvalidArgumentType(
                '$value->alternativeText',
                'string',
                $value->alternativeText
            );
        }

        if (isset($value->fileSize) && ((!is_int($value->fileSize) && !is_float($value->fileSize)) || $value->fileSize < 0)) {
            throw new InvalidArgumentType(
                '$value->fileSize',
                'numeric',
                $value->fileSize
            );
        }

        if (isset($value->additionalData) && !\is_array($value->additionalData)) {
            throw new InvalidArgumentType('$value->additionalData', 'array', $value->additionalData);
        }
    }

    /**
     * Validates a field based on the validators in the field definition.
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException
     *
     * @param \Ibexa\Contracts\Core\Repository\Values\ContentType\FieldDefinition $fieldDefinition The field definition of the field
     * @param \Ibexa\Core\FieldType\Image\Value $fieldValue The field value for which an action is performed
     *
     * @return \Ibexa\Contracts\Core\FieldType\ValidationError[]
     */
    public function validate(FieldDefinition $fieldDefinition, SPIValue $fieldValue)
    {
        $errors = [];

        if ($this->isEmptyValue($fieldValue)) {
            return $errors;
        }

        foreach ($this->validators as $externalValidator) {
            if (!$externalValidator->validate($fieldValue, $fieldDefinition)) {
                $errors = array_merge($errors, $externalValidator->getMessage());
            }
        }

        foreach ((array)$fieldDefinition->getValidatorConfiguration() as $validatorIdentifier => $parameters) {
            switch ($validatorIdentifier) {
                case 'FileSizeValidator':
                    if (empty($parameters['maxFileSize'])) {
                        // No file size limit
                        break;
                    }

                    // Database stores maxFileSize in MB
                    if (($parameters['maxFileSize'] * 1024 * 1024) < $fieldValue->fileSize) {
                        $errors[] = new ValidationError(
                            'The file size cannot exceed %size% megabyte.',
                            'The file size cannot exceed %size% megabytes.',
                            [
                                '%size%' => $parameters['maxFileSize'],
                            ],
                            'fileSize'
                        );
                    }
                    break;
                case 'AlternativeTextValidator':
                    if ($parameters['required'] && $fieldValue->isAlternativeTextEmpty()) {
                        $errors[] = new ValidationError(
                            'Alternative text is required.',
                            null,
                            [],
                            'alternativeText'
                        );
                    }
                    break;
            }
        }

        return $errors;
    }

    public function validateFieldSettings($fieldSettings): array
    {
        $validationErrors = [];

        foreach ($fieldSettings as $name => $value) {
            if (!isset($this->settingsSchema[$name])) {
                $validationErrors[] = new ValidationError(
                    "Setting '%setting%' is unknown",
                    null,
                    [
                        '%setting%' => $name,
                    ],
                    "[$name]"
                );
            }

            if (
                $name === 'mimeTypes'
                && !empty($this->mimeTypes)
                && !empty(array_diff($value, $this->mimeTypes))
            ) {
                $validationErrors[] = new ValidationError(
                    "Setting '%setting%' contains unsupported mime types",
                    null,
                    [
                        '%setting%' => $name,
                    ],
                    "[$name]"
                );
            }
        }

        return $validationErrors;
    }

    /**
     * Validates the validatorConfiguration of a FieldDefinitionCreateStruct or FieldDefinitionUpdateStruct.
     *
     * @param mixed $validatorConfiguration
     *
     * @return \Ibexa\Contracts\Core\FieldType\ValidationError[]
     */
    public function validateValidatorConfiguration($validatorConfiguration)
    {
        $validationErrors = [];

        foreach ($validatorConfiguration as $validatorIdentifier => $parameters) {
            switch ($validatorIdentifier) {
                case 'FileSizeValidator':
                    if (!array_key_exists('maxFileSize', $parameters)) {
                        $validationErrors[] = new ValidationError(
                            'Validator %validator% expects parameter %parameter% to be set.',
                            null,
                            [
                                '%validator%' => $validatorIdentifier,
                                '%parameter%' => 'maxFileSize',
                            ],
                            "[$validatorIdentifier]"
                        );
                        break;
                    }
                    if (!is_numeric($parameters['maxFileSize']) && $parameters['maxFileSize'] !== null) {
                        $validationErrors[] = new ValidationError(
                            'Validator %validator% expects parameter %parameter% to be of %type%.',
                            null,
                            [
                                '%validator%' => $validatorIdentifier,
                                '%parameter%' => 'maxFileSize',
                                '%type%' => 'numeric',
                            ],
                            "[$validatorIdentifier][maxFileSize]"
                        );
                    }
                    break;
                case 'AlternativeTextValidator':
                    if (!array_key_exists('required', $parameters)) {
                        $validationErrors[] = new ValidationError(
                            'Validator %validator% expects parameter %parameter% to be set.',
                            null,
                            [
                                '%validator%' => $validatorIdentifier,
                                '%parameter%' => 'required',
                            ],
                            "[$validatorIdentifier]"
                        );
                    }
                    break;
                default:
                    $validationErrors[] = new ValidationError(
                        "Validator '%validator%' is unknown",
                        null,
                        [
                            '%validator%' => $validatorIdentifier,
                        ],
                        "[$validatorIdentifier]"
                    );
            }
        }

        return $validationErrors;
    }

    /**
     * {@inheritdoc}
     */
    protected function getSortInfo(BaseValue $value): bool
    {
        return false;
    }

    /**
     * Converts an $hash to the Value defined by the field type.
     *
     * @param mixed $hash
     *
     * @return \Ibexa\Core\FieldType\Image\Value $value
     */
    public function fromHash($hash)
    {
        if ($hash === null) {
            return $this->getEmptyValue();
        }

        return new Value($hash);
    }

    /**
     * Converts a $Value to a hash.
     *
     * @param \Ibexa\Core\FieldType\Image\Value $value
     *
     * @return mixed
     */
    public function toHash(SPIValue $value)
    {
        if ($this->isEmptyValue($value)) {
            return null;
        }

        return [
            'id' => $value->id,
            'alternativeText' => $value->alternativeText,
            'fileName' => $value->fileName,
            'fileSize' => $value->fileSize,
            'imageId' => $value->imageId,
            'uri' => $value->uri,
            'inputUri' => $value->inputUri,
            'width' => $value->width,
            'height' => $value->height,
            'additionalData' => $value->additionalData,
            'mime' => $value->mime,
        ];
    }

    /**
     * Converts a $value to a persistence value.
     *
     * @param \Ibexa\Core\FieldType\Image\Value $value
     *
     * @return \Ibexa\Contracts\Core\Persistence\Content\FieldValue
     */
    public function toPersistenceValue(SPIValue $value)
    {
        // Store original data as external (to indicate they need to be stored)
        return new FieldValue(
            [
                'data' => null,
                'externalData' => $this->toHash($value),
                'sortKey' => $this->getSortInfo($value),
            ]
        );
    }

    /**
     * Converts a persistence $fieldValue to a Value.
     *
     * @param \Ibexa\Contracts\Core\Persistence\Content\FieldValue $fieldValue
     *
     * @return \Ibexa\Core\FieldType\Image\Value
     */
    public function fromPersistenceValue(FieldValue $fieldValue)
    {
        if ($fieldValue->data === null) {
            return $this->getEmptyValue();
        }

        // Restored data comes in $data, since it has already been processed
        // there might be more data in the persistence value than needed here
        $result = $this->fromHash(
            [
                'id' => (isset($fieldValue->data['id'])
                    ? $fieldValue->data['id']
                    : null),
                'alternativeText' => (isset($fieldValue->data['alternativeText'])
                    ? $fieldValue->data['alternativeText']
                    : null),
                'fileName' => (isset($fieldValue->data['fileName'])
                    ? $fieldValue->data['fileName']
                    : null),
                'fileSize' => (isset($fieldValue->data['fileSize'])
                    ? $fieldValue->data['fileSize']
                    : null),
                'uri' => (isset($fieldValue->data['uri'])
                    ? $fieldValue->data['uri']
                    : null),
                'imageId' => (isset($fieldValue->data['imageId'])
                    ? $fieldValue->data['imageId']
                    : null),
                'width' => (isset($fieldValue->data['width'])
                    ? $fieldValue->data['width']
                    : null),
                'height' => (isset($fieldValue->data['height'])
                    ? $fieldValue->data['height']
                    : null),
                'additionalData' => $fieldValue->data['additionalData'] ?? [],
                'mime' => $fieldValue->data['mime'] ?? null,
            ]
        );

        return $result;
    }

    public function valuesEqual(SPIValue $value1, SPIValue $value2): bool
    {
        $hashValue1 = $this->toHash($value1);
        $hashValue2 = $this->toHash($value2);

        unset($hashValue1['imageId'], $hashValue2['imageId']);

        return $hashValue1 === $hashValue2;
    }

    public static function getTranslationMessages(): array
    {
        return [
            Message::create('ibexa_image.name', 'ibexa_fieldtypes')->setDesc('Image'),
        ];
    }

    public function isSearchable(): bool
    {
        return true;
    }
}
