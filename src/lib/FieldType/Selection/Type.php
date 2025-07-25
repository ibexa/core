<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\FieldType\Selection;

use Ibexa\Contracts\Core\FieldType\Value as SPIValue;
use Ibexa\Contracts\Core\Repository\Values\ContentType\FieldDefinition;
use Ibexa\Core\Base\Exceptions\InvalidArgumentType;
use Ibexa\Core\FieldType\FieldType;
use Ibexa\Core\FieldType\ValidationError;
use Ibexa\Core\FieldType\Value as BaseValue;
use JMS\TranslationBundle\Model\Message;
use JMS\TranslationBundle\Translation\TranslationContainerInterface;

/**
 * The Selection field type.
 *
 * This field type represents a simple string.
 */
class Type extends FieldType implements TranslationContainerInterface
{
    /**
     * The setting keys which are available on this field type.
     *
     * The key is the setting name, and the value is the default value for given
     * setting, set to null if no particular default should be set.
     *
     * @var mixed
     */
    protected array $settingsSchema = [
        'isMultiple' => [
            'type' => 'bool',
            'default' => false,
        ],
        'options' => [
            'type' => 'hash',
            'default' => [],
        ],
        'multilingualOptions' => [
            'type' => 'hash',
            'default' => [],
        ],
    ];

    public function validateFieldSettings(array $fieldSettings): array
    {
        $validationErrors = [];

        foreach ($fieldSettings as $settingKey => $settingValue) {
            switch ($settingKey) {
                case 'isMultiple':
                    if (!is_bool($settingValue)) {
                        $validationErrors[] = new ValidationError(
                            "FieldType '%fieldType%' expects setting '%setting%' to be of type '%type%'",
                            null,
                            [
                                '%fieldType%' => $this->getFieldTypeIdentifier(),
                                '%setting%' => $settingKey,
                                '%type%' => 'bool',
                            ],
                            "[$settingKey]"
                        );
                    }
                    break;
                case 'options':
                    if (!is_array($settingValue)) {
                        $validationErrors[] = new ValidationError(
                            "FieldType '%fieldType%' expects setting '%setting%' to be of type '%type%'",
                            null,
                            [
                                '%fieldType%' => $this->getFieldTypeIdentifier(),
                                '%setting%' => $settingKey,
                                '%type%' => 'hash',
                            ],
                            "[$settingKey]"
                        );
                    }
                    break;
                case 'multilingualOptions':
                    if (!is_array($settingValue) && !is_array(reset($settingValue))) {
                        $validationErrors[] = new ValidationError(
                            "FieldType '%fieldType%' expects setting '%setting%' to be of type '%type%'",
                            null,
                            [
                                '%fieldType%' => $this->getFieldTypeIdentifier(),
                                '%setting%' => $settingKey,
                                '%type%' => 'hash',
                            ],
                            "[$settingKey]"
                        );
                    }
                    break;
                default:
                    $validationErrors[] = new ValidationError(
                        "Setting '%setting%' is unknown",
                        null,
                        [
                            '%setting%' => $settingKey,
                        ],
                        "[$settingKey]"
                    );
            }
        }

        return $validationErrors;
    }

    /**
     * Returns the field type identifier for this field type.
     *
     * @return string
     */
    public function getFieldTypeIdentifier(): string
    {
        return 'ibexa_selection';
    }

    /**
     * @param \Ibexa\Core\FieldType\Selection\Value|\Ibexa\Contracts\Core\FieldType\Value $value
     */
    public function getName(SPIValue $value, FieldDefinition $fieldDefinition, string $languageCode): string
    {
        if (empty($value->selection)) {
            return '';
        }

        $names = [];
        $fieldSettings = $fieldDefinition->getFieldSettings();

        foreach ($value->selection as $optionIndex) {
            if (isset($fieldSettings['multilingualOptions'][$languageCode][$optionIndex])) {
                $names[] = $fieldSettings['multilingualOptions'][$languageCode][$optionIndex];
            } elseif (isset($fieldSettings['multilingualOptions'][$fieldDefinition->mainLanguageCode][$optionIndex])) {
                $names[] = $fieldSettings['multilingualOptions'][$fieldDefinition->mainLanguageCode][$optionIndex];
            } elseif (isset($fieldSettings['options'][$optionIndex])) {
                $names[] = $fieldSettings['options'][$optionIndex];
            }
        }

        return implode(' ', $names);
    }

    public function getEmptyValue(): Value
    {
        return new Value();
    }

    /**
     * Inspects given $inputValue and potentially converts it into a dedicated value object.
     *
     * @param array|\Ibexa\Core\FieldType\Selection\Value $inputValue
     *
     * @return \Ibexa\Core\FieldType\Selection\Value The potentially converted and structurally plausible value.
     */
    protected function createValueFromInput($inputValue)
    {
        if (is_array($inputValue)) {
            $inputValue = new Value($inputValue);
        }

        return $inputValue;
    }

    /**
     * Throws an exception if value structure is not of expected format.
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException If the value does not match the expected structure.
     *
     * @param \Ibexa\Core\FieldType\Selection\Value $value
     */
    protected function checkValueStructure(BaseValue $value)
    {
        if (!is_array($value->selection)) {
            throw new InvalidArgumentType(
                '$value->selection',
                'array',
                $value->selection
            );
        }
    }

    /**
     * Validates field value against 'isMultiple' and 'options' settings.
     *
     * Does not use validators.
     *
     * @param \Ibexa\Contracts\Core\Repository\Values\ContentType\FieldDefinition $fieldDef The field definition of the field
     * @param \Ibexa\Core\FieldType\Selection\Value $value The field value for which an action is performed
     *
     * @return \Ibexa\Contracts\Core\FieldType\ValidationError[]
     *
     *@throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException
     */
    public function validate(FieldDefinition $fieldDef, SPIValue $value): array
    {
        $validationErrors = [];

        if ($this->isEmptyValue($value)) {
            return $validationErrors;
        }

        $fieldSettings = $fieldDef->getFieldSettings();

        if ((!isset($fieldSettings['isMultiple']) || $fieldSettings['isMultiple'] === false)
            && count($value->selection) > 1) {
            $validationErrors[] = new ValidationError(
                'Field definition does not allow multiple options to be selected.',
                null,
                [],
                'selection'
            );
        }

        foreach ($value->selection as $optionIndex) {
            if (!isset($fieldSettings['options'][$optionIndex]) && empty($fieldSettings['multilingualOptions'])) {
                $validationErrors[] = new ValidationError(
                    'Option with index %index% does not exist in the field definition.',
                    null,
                    [
                        '%index%' => $optionIndex,
                    ],
                    'selection'
                );
            }
        }

        //@todo: find a way to include selection language
        if (isset($fieldSettings['multilingualOptions'])) {
            $possibleOptionIndexesByLanguage = array_map(static function ($languageOptionIndexes): array {
                return array_keys($languageOptionIndexes);
            }, $fieldSettings['multilingualOptions']);

            $possibleOptionIndexes = array_merge(...array_values($possibleOptionIndexesByLanguage));

            foreach ($value->selection as $optionIndex) {
                if (!in_array($optionIndex, $possibleOptionIndexes)) {
                    $validationErrors[] = new ValidationError(
                        'Option with index %index% does not exist in the field definition.',
                        null,
                        [
                            '%index%' => $optionIndex,
                        ],
                        'selection'
                    );
                }
            }
        }

        return $validationErrors;
    }

    /**
     * @param \Ibexa\Core\FieldType\Selection\Value $value
     */
    protected function getSortInfo(SPIValue $value): string
    {
        return implode('-', $value->selection);
    }

    public function fromHash(mixed $hash): Value
    {
        return new Value($hash);
    }

    /**
     * @param \Ibexa\Core\FieldType\Selection\Value $value
     *
     * @return int[]
     */
    public function toHash(SPIValue $value): array
    {
        return $value->selection;
    }

    public function isSearchable(): bool
    {
        return true;
    }

    public static function getTranslationMessages(): array
    {
        return [
            Message::create('ibexa_selection.name', 'ibexa_fieldtypes')->setDesc('Selection'),
        ];
    }
}
