<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

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
    protected array $validatorConfigurationSchema = [
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
    protected array $settingsSchema = [
        'mimeTypes' => [
            'type' => 'choice',
            'default' => [],
        ],
    ];

    /** @var \Ibexa\Core\FieldType\Validator[] */
    private array $validators;

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

    public function getEmptyValue(): Value
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
     * @param \Ibexa\Contracts\Core\Repository\Values\ContentType\FieldDefinition $fieldDef The field definition of the field
     * @param \Ibexa\Core\FieldType\Image\Value $value The field value for which an action is performed
     *
     * @return \Ibexa\Contracts\Core\FieldType\ValidationError[]
     *
     *@throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException
     */
    public function validate(FieldDefinition $fieldDef, SPIValue $value): array
    {
        $errors = [];

        if ($this->isEmptyValue($value)) {
            return $errors;
        }

        foreach ($this->validators as $externalValidator) {
            if (!$externalValidator->validate($value, $fieldDef)) {
                $errors = array_merge($errors, $externalValidator->getMessage());
            }
        }

        foreach ((array)$fieldDef->getValidatorConfiguration() as $validatorIdentifier => $parameters) {
            switch ($validatorIdentifier) {
                case 'FileSizeValidator':
                    if (empty($parameters['maxFileSize'])) {
                        // No file size limit
                        break;
                    }

                    // Database stores maxFileSize in MB
                    if (($parameters['maxFileSize'] * 1024 * 1024) < $value->fileSize) {
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
                    if ($parameters['required'] && $value->isAlternativeTextEmpty()) {
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

    public function validateFieldSettings(array $fieldSettings): array
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
    public function validateValidatorConfiguration(mixed $validatorConfiguration): array
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
     * @param \Ibexa\Core\FieldType\Image\Value $value
     */
    protected function getSortInfo(SPIValue $value): false
    {
        return false;
    }

    /**
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException
     */
    public function fromHash(mixed $hash): Value
    {
        if ($hash === null) {
            return $this->getEmptyValue();
        }

        return new Value($hash);
    }

    /**
     * @param \Ibexa\Core\FieldType\Image\Value $value
     *
     * @return array<string, mixed>|null
     */
    public function toHash(SPIValue $value): ?array
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
     * @param \Ibexa\Core\FieldType\Image\Value $value
     */
    public function toPersistenceValue(SPIValue $value): FieldValue
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
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException
     */
    public function fromPersistenceValue(FieldValue $fieldValue): Value
    {
        if ($fieldValue->data === null) {
            return $this->getEmptyValue();
        }

        // Restored data comes in $data, since it has already been processed
        // there might be more data in the persistence value than needed here
        return $this->fromHash(
            [
                'id' => $fieldValue->data['id'] ?? null,
                'alternativeText' => $fieldValue->data['alternativeText'] ?? null,
                'fileName' => $fieldValue->data['fileName'] ?? null,
                'fileSize' => $fieldValue->data['fileSize'] ?? null,
                'uri' => $fieldValue->data['uri'] ?? null,
                'imageId' => $fieldValue->data['imageId'] ?? null,
                'width' => $fieldValue->data['width'] ?? null,
                'height' => $fieldValue->data['height'] ?? null,
                'additionalData' => $fieldValue->data['additionalData'] ?? [],
                'mime' => $fieldValue->data['mime'] ?? null,
            ]
        );
    }

    /**
     * @param \Ibexa\Core\FieldType\Image\Value $value1
     * @param \Ibexa\Core\FieldType\Image\Value $value2
     */
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
