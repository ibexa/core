<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Core\FieldType\TextBlock;

use Ibexa\Core\Base\Exceptions\InvalidArgumentType;
use Ibexa\Core\FieldType\BaseTextType;
use Ibexa\Core\FieldType\ValidationError;
use Ibexa\Core\FieldType\Value as BaseValue;
use JMS\TranslationBundle\Model\Message;
use JMS\TranslationBundle\Translation\TranslationContainerInterface;

/**
 * The TextBlock field type.
 *
 * Represents a larger body of text, such as text areas.
 */
class Type extends BaseTextType implements TranslationContainerInterface
{
    protected $settingsSchema = [
        'textRows' => [
            'type' => 'int',
            'default' => 10,
        ],
    ];

    protected $validatorConfigurationSchema = [];

    public function getFieldTypeIdentifier(): string
    {
        return 'ibexa_text';
    }

    public function getEmptyValue(): Value
    {
        return new Value();
    }

    /**
     * @param string|\Ibexa\Core\FieldType\TextBlock\Value $inputValue
     *
     * @return \Ibexa\Core\FieldType\TextBlock\Value The potentially converted and structurally plausible value.
     */
    protected function createValueFromInput($inputValue)
    {
        if (is_string($inputValue)) {
            $inputValue = new Value($inputValue);
        }

        return $inputValue;
    }

    protected static function checkValueType(mixed $value): void
    {
        if (!$value instanceof Value) {
            throw new InvalidArgumentType('$value', Value::class, $value);
        }
    }

    /**
     * Returns information for FieldValue->$sortKey relevant to the field type.
     *
     * @param \Ibexa\Core\FieldType\TextBlock\Value $value
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException
     */
    protected function getSortInfo(BaseValue $value): string
    {
        $tokens = strtok(trim($value->text), "\r\n");

        return $tokens !== false
            ? $this->transformationProcessor->transformByGroup(mb_substr($tokens, 0, 255), 'lowercase')
            : '';
    }

    /**
     * @param string $hash
     */
    public function fromHash($hash): Value
    {
        if ($hash === null) {
            return $this->getEmptyValue();
        }

        return new Value($hash);
    }

    /**
     * Validates the fieldSettings of a FieldDefinitionCreateStruct or FieldDefinitionUpdateStruct.
     *
     * @param mixed $fieldSettings
     *
     * @return \Ibexa\Contracts\Core\FieldType\ValidationError[]
     */
    public function validateFieldSettings($fieldSettings): array
    {
        $validationErrors = [];

        foreach ($fieldSettings as $name => $value) {
            if (isset($this->settingsSchema[$name])) {
                if ($name === 'textRows' && !is_int($value)) {
                    $validationErrors[] = new ValidationError(
                        "Setting '%setting%' value must be of integer type",
                        null,
                        [
                            '%setting%' => $name,
                        ],
                        "[$name]"
                    );
                }
            } else {
                $validationErrors[] = new ValidationError(
                    "Setting '%setting%' is unknown",
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

    public static function getTranslationMessages(): array
    {
        return [
            Message::create('ibexa_text.name', 'ibexa_fieldtypes')->setDesc('Text block'),
        ];
    }
}
