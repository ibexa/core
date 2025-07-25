<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\FieldType\Country;

use Ibexa\Contracts\Core\FieldType\Value as SPIValue;
use Ibexa\Contracts\Core\Repository\Values\ContentType\FieldDefinition;
use Ibexa\Core\Base\Exceptions\InvalidArgumentType;
use Ibexa\Core\FieldType\Country\Exception\InvalidValue;
use Ibexa\Core\FieldType\FieldType;
use Ibexa\Core\FieldType\ValidationError;
use Ibexa\Core\FieldType\Value as BaseValue;
use JMS\TranslationBundle\Model\Message;
use JMS\TranslationBundle\Translation\TranslationContainerInterface;

/**
 * The Country field type.
 *
 * This field type represents a simple string.
 */
class Type extends FieldType implements TranslationContainerInterface
{
    protected array $settingsSchema = [
        'isMultiple' => [
            'type' => 'boolean',
            'default' => false,
        ],
    ];

    /** @var array */
    protected $countriesInfo;

    /**
     * @param array $countriesInfo Array of countries data
     */
    public function __construct(array $countriesInfo)
    {
        $this->countriesInfo = $countriesInfo;
    }

    /**
     * Returns the field type identifier for this field type.
     *
     * @return string
     */
    public function getFieldTypeIdentifier(): string
    {
        return 'ibexa_country';
    }

    /**
     * @param \Ibexa\Core\FieldType\Country\Value|\Ibexa\Contracts\Core\FieldType\Value $value
     */
    public function getName(SPIValue $value, FieldDefinition $fieldDefinition, string $languageCode): string
    {
        return (string)$value;
    }

    public function getEmptyValue(): Value
    {
        return new Value();
    }

    /**
     * Inspects given $inputValue and potentially converts it into a dedicated value object.
     *
     * @param array|\Ibexa\Core\FieldType\Country\Value $inputValue
     *
     * @return \Ibexa\Core\FieldType\Country\Value The potentially converted and structurally plausible value.
     */
    protected function createValueFromInput($inputValue)
    {
        if (is_array($inputValue)) {
            $inputValue = $this->fromHash($inputValue);
        }

        return $inputValue;
    }

    /**
     * Throws an exception if value structure is not of expected format.
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException If the value does not match the expected structure.
     *
     * @param \Ibexa\Core\FieldType\Country\Value $value
     */
    protected function checkValueStructure(BaseValue $value)
    {
        if (!is_array($value->countries)) {
            throw new InvalidArgumentType(
                '$value->countries',
                'array',
                $value->countries
            );
        }
    }

    /**
     * Validates field value against 'isMultiple' setting.
     *
     * Does not use validators.
     *
     * @param \Ibexa\Contracts\Core\Repository\Values\ContentType\FieldDefinition $fieldDef The field definition of the field
     * @param \Ibexa\Core\FieldType\Country\Value $value The field value for which an action is performed
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
            && count($value->countries) > 1) {
            $validationErrors[] = new ValidationError(
                'Field definition does not allow multiple countries to be selected.',
                null,
                [],
                'countries'
            );
        }

        foreach ($value->countries as $alpha2 => $countryInfo) {
            if (!isset($this->countriesInfo[$alpha2])) {
                $validationErrors[] = new ValidationError(
                    "Country with Alpha2 code '%alpha2%' is not defined in FieldType settings.",
                    null,
                    [
                        '%alpha2%' => $alpha2,
                    ],
                    'countries'
                );
            }
        }

        return $validationErrors;
    }

    /**
     * @param \Ibexa\Core\FieldType\Country\Value $value
     */
    protected function getSortInfo(SPIValue $value): string
    {
        $countries = [];
        foreach ($value->countries as $countryInfo) {
            $countries[] = $this->transformationProcessor->transformByGroup($countryInfo['Name'], 'lowercase');
        }

        sort($countries);

        return implode(',', $countries);
    }

    public function fromHash(mixed $hash): Value
    {
        if ($hash === null) {
            return $this->getEmptyValue();
        }

        $countries = [];
        foreach ($hash as $country) {
            foreach ($this->countriesInfo as $countryInfo) {
                switch ($country) {
                    case $countryInfo['Name']:
                    case $countryInfo['Alpha2']:
                    case $countryInfo['Alpha3']:
                        $countries[$countryInfo['Alpha2']] = $countryInfo;
                        continue 3;
                }
            }

            throw new InvalidValue($country);
        }

        return new Value($countries);
    }

    /**
     * @param \Ibexa\Core\FieldType\Country\Value $value
     *
     * @return string[]|null
     */
    public function toHash(SPIValue $value): ?array
    {
        if ($this->isEmptyValue($value)) {
            return null;
        }

        return array_keys($value->countries);
    }

    public function isSearchable(): bool
    {
        return true;
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
                continue;
            }

            switch ($name) {
                case 'isMultiple':
                    if (!is_bool($value)) {
                        $validationErrors[] = new ValidationError(
                            "Setting '%setting%' value must be of boolean type",
                            null,
                            [
                                '%setting%' => $name,
                            ],
                            "[$name]"
                        );
                    }
                    break;
            }
        }

        return $validationErrors;
    }

    public static function getTranslationMessages(): array
    {
        return [
            Message::create('ibexa_country.name', 'ibexa_fieldtypes')->setDesc('Country'),
        ];
    }
}
