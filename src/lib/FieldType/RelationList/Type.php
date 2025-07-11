<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Core\FieldType\RelationList;

use Ibexa\Contracts\Core\FieldType\Value as SPIValue;
use Ibexa\Contracts\Core\Persistence\Content\Handler as SPIContentHandler;
use Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException;
use Ibexa\Contracts\Core\Repository\Values\Content\ContentInfo;
use Ibexa\Contracts\Core\Repository\Values\Content\Relation;
use Ibexa\Contracts\Core\Repository\Values\Content\RelationType;
use Ibexa\Contracts\Core\Repository\Values\ContentType\FieldDefinition;
use Ibexa\Core\Base\Exceptions\InvalidArgumentType;
use Ibexa\Core\FieldType\FieldType;
use Ibexa\Core\FieldType\ValidationError;
use Ibexa\Core\FieldType\Value as BaseValue;
use Ibexa\Core\Repository\Validator\TargetContentValidatorInterface;
use JMS\TranslationBundle\Model\Message;
use JMS\TranslationBundle\Translation\TranslationContainerInterface;

/**
 * The RelationList field type.
 *
 * This field type represents a relation to a content.
 *
 * hash format ({@see fromhash()}, {@see toHash()}):
 * array( 'destinationContentIds' => array( (int)$destinationContentId ) );
 */
class Type extends FieldType implements TranslationContainerInterface
{
    public const SELECTION_BROWSE = 0;
    /**
     * @todo following selection methods comes from legacy and may be interpreted as SELECTION_BROWSE by UI.
     * UI support will be evaluated on a case by case basis for future versions.
     */
    public const SELECTION_DROPDOWN = 1;
    public const SELECTION_LIST_WITH_RADIO_BUTTONS = 2;
    public const SELECTION_LIST_WITH_CHECKBOXES = 3;
    public const SELECTION_MULTIPLE_SELECTION_LIST = 4;
    public const SELECTION_TEMPLATE_BASED_MULTIPLE = 5;
    public const SELECTION_TEMPLATE_BASED_SINGLE = 6;

    protected $settingsSchema = [
        'selectionMethod' => [
            'type' => 'int',
            'default' => self::SELECTION_BROWSE,
        ],
        'selectionDefaultLocation' => [
            'type' => 'string',
            'default' => null,
        ],
        'rootDefaultLocation' => [
            'type' => 'bool',
            'default' => false,
        ],
        'selectionContentTypes' => [
            'type' => 'array',
            'default' => [],
        ],
    ];

    protected $validatorConfigurationSchema = [
        'RelationListValueValidator' => [
            'selectionLimit' => [
                'type' => 'int',
                'default' => 0,
            ],
        ],
    ];

    /** @var \Ibexa\Contracts\Core\Persistence\Content\Handler */
    private $handler;

    /** @var \Ibexa\Core\Repository\Validator\TargetContentValidatorInterface */
    private $targetContentValidator;

    public function __construct(
        SPIContentHandler $handler,
        TargetContentValidatorInterface $targetContentValidator
    ) {
        $this->handler = $handler;
        $this->targetContentValidator = $targetContentValidator;
    }

    public function validateFieldSettings($fieldSettings)
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
                case 'selectionMethod':
                    if (!$this->isValidSelectionMethod($value)) {
                        $validationErrors[] = new ValidationError(
                            "The following options are available for setting '%setting%': %selection_browse%, %selection_dropdown%, %selection_list_with_radio_buttons%, %selection_list_with_checkboxes%, %selection_multiple_selection_list%, %selection_template_based_multiple%, %selection_template_based_single%",
                            null,
                            [
                                '%setting%' => $name,
                                '%selection_browse%' => self::SELECTION_BROWSE,
                                '%selection_dropdown%' => self::SELECTION_DROPDOWN,
                                '%selection_list_with_radio_buttons%' => self::SELECTION_LIST_WITH_RADIO_BUTTONS,
                                '%selection_list_with_checkboxes%' => self::SELECTION_LIST_WITH_CHECKBOXES,
                                '%selection_multiple_selection_list%' => self::SELECTION_MULTIPLE_SELECTION_LIST,
                                '%selection_template_based_multiple%' => self::SELECTION_TEMPLATE_BASED_MULTIPLE,
                                '%selection_template_based_single%' => self::SELECTION_TEMPLATE_BASED_SINGLE,
                            ],
                            "[$name]"
                        );
                    }
                    break;
                case 'selectionDefaultLocation':
                    if (!is_int($value) && !is_string($value) && $value !== null) {
                        $validationErrors[] = new ValidationError(
                            "Setting '%setting%' value must be of either null, string or integer",
                            null,
                            [
                                '%setting%' => $name,
                            ],
                            "[$name]"
                        );
                    }
                    break;
                case 'rootDefaultLocation':
                    if (!is_bool($value) && $value !== null) {
                        $validationErrors[] = new ValidationError(
                            "Setting '%setting%' value must be of either null or bool",
                            null,
                            [
                                '%setting%' => $name,
                            ],
                            "[$name]"
                        );
                    }
                    break;
                case 'selectionContentTypes':
                    if (!is_array($value)) {
                        $validationErrors[] = new ValidationError(
                            "Setting '%setting%' value must be of array type",
                            null,
                            [
                                '%setting%' => $name,
                            ],
                            "[$name]"
                        );
                    }
                    break;
                case 'selectionLimit':
                    if (!is_int($value)) {
                        $validationErrors[] = new ValidationError(
                            "Setting '%setting%' value must be of integer type",
                            null,
                            [
                                '%setting%' => $name,
                            ],
                            "[$name]"
                        );
                    }
                    if ($value < 0) {
                        $validationErrors[] = new ValidationError(
                            "Setting '%setting%' value cannot be lower than 0",
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

        foreach ($validatorConfiguration as $validatorIdentifier => $constraints) {
            if ($validatorIdentifier !== 'RelationListValueValidator') {
                $validationErrors[] = new ValidationError(
                    "Validator '%validator%' is unknown",
                    null,
                    [
                        '%validator%' => $validatorIdentifier,
                    ],
                    "[$validatorIdentifier]"
                );

                continue;
            }

            foreach ($constraints as $name => $value) {
                if ($name === 'selectionLimit') {
                    if (!is_int($value) && !ctype_digit($value)) {
                        $validationErrors[] = new ValidationError(
                            "Validator parameter '%parameter%' value must be an integer",
                            null,
                            [
                                '%parameter%' => $name,
                            ],
                            "[$validatorIdentifier][$name]"
                        );
                    }
                    if ($value < 0) {
                        $validationErrors[] = new ValidationError(
                            "Validator parameter '%parameter%' value must be equal to/greater than 0",
                            null,
                            [
                                '%parameter%' => $name,
                            ],
                            "[$validatorIdentifier][$name]"
                        );
                    }
                } else {
                    $validationErrors[] = new ValidationError(
                        "Validator parameter '%parameter%' is unknown",
                        null,
                        [
                            '%parameter%' => $name,
                        ],
                        "[$validatorIdentifier][$name]"
                    );
                }
            }
        }

        return $validationErrors;
    }

    /**
     * Validates a field based on the validators in the field definition.
     *
     * @return \Ibexa\Contracts\Core\FieldType\ValidationError[]
     */
    public function validate(FieldDefinition $fieldDefinition, SPIValue $fieldValue): array
    {
        $validationErrors = [];

        if ($this->isEmptyValue($fieldValue)) {
            return $validationErrors;
        }

        $validatorConfiguration = $fieldDefinition->getValidatorConfiguration();
        $constraints = $validatorConfiguration['RelationListValueValidator'] ?? [];

        $validationErrors = [];

        if (isset($constraints['selectionLimit']) &&
            $constraints['selectionLimit'] > 0 && count($fieldValue->destinationContentIds) > $constraints['selectionLimit']) {
            $validationErrors[] = new ValidationError(
                'The selected content items number cannot be higher than %limit%.',
                null,
                [
                    '%limit%' => $constraints['selectionLimit'],
                ],
                'destinationContentIds'
            );
        }

        $allowedContentTypes = $fieldDefinition->getFieldSettings()['selectionContentTypes'] ?? [];

        foreach ($fieldValue->destinationContentIds as $destinationContentId) {
            $validationError = $this->targetContentValidator->validate((int) $destinationContentId, $allowedContentTypes);
            if ($validationError !== null) {
                $validationErrors[] = $validationError;
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
        return 'ibexa_object_relation_list';
    }

    /**
     * @param \Ibexa\Core\FieldType\RelationList\Value|\Ibexa\Contracts\Core\FieldType\Value $value
     */
    public function getName(SPIValue $value, FieldDefinition $fieldDefinition, string $languageCode): string
    {
        if (empty($value->destinationContentIds)) {
            return '';
        }

        $names = [];
        foreach ($value->destinationContentIds as $contentId) {
            try {
                $contentInfo = $this->handler->loadContentInfo($contentId);
                $versionInfo = $this->handler->loadVersionInfo($contentId, $contentInfo->currentVersionNo);
            } catch (NotFoundException $e) {
                continue;
            }

            if (isset($versionInfo->names[$languageCode])) {
                $names[] = $versionInfo->names[$languageCode];
            } else {
                $names[] = $versionInfo->names[$contentInfo->mainLanguageCode];
            }
        }

        return implode(' ', $names);
    }

    /**
     * Returns the fallback default value of field type when no such default
     * value is provided in the field definition in content types.
     *
     * @return \Ibexa\Core\FieldType\RelationList\Value
     */
    public function getEmptyValue()
    {
        return new Value();
    }

    /**
     * Inspects given $inputValue and potentially converts it into a dedicated value object.
     *
     * @param int|string|array|\Ibexa\Contracts\Core\Repository\Values\Content\ContentInfo|\Ibexa\Core\FieldType\RelationList\Value $inputValue
     *
     * @return \Ibexa\Core\FieldType\RelationList\Value The potentially converted and structurally plausible value.
     */
    protected function createValueFromInput($inputValue)
    {
        // ContentInfo
        if ($inputValue instanceof ContentInfo) {
            $inputValue = new Value([$inputValue->id]);
        } elseif (is_int($inputValue) || is_string($inputValue)) {
            // content id
            $inputValue = new Value([$inputValue]);
        } elseif (is_array($inputValue)) {
            // content id's
            $inputValue = new Value($inputValue);
        }

        return $inputValue;
    }

    /**
     * Throws an exception if value structure is not of expected format.
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException If the value does not match the expected structure.
     *
     * @param \Ibexa\Core\FieldType\RelationList\Value $value
     */
    protected function checkValueStructure(BaseValue $value)
    {
        if (!is_array($value->destinationContentIds)) {
            throw new InvalidArgumentType(
                '$value->destinationContentIds',
                'array',
                $value->destinationContentIds
            );
        }

        foreach ($value->destinationContentIds as $key => $destinationContentId) {
            if (!is_int($destinationContentId) && !is_string($destinationContentId)) {
                throw new InvalidArgumentType(
                    "\$value->destinationContentIds[$key]",
                    'string|int',
                    $destinationContentId
                );
            }
        }
    }

    /**
     * Returns information for FieldValue->$sortKey relevant to the field type.
     *
     * For this FieldType, the related objects IDs are returned, separated by ",".
     *
     * @param \Ibexa\Core\FieldType\RelationList\Value $value
     *
     * @return string
     */
    protected function getSortInfo(BaseValue $value): string
    {
        return implode(',', $value->destinationContentIds);
    }

    /**
     * Converts an $hash to the Value defined by the field type.
     *
     * @param mixed $hash
     *
     * @return \Ibexa\Core\FieldType\RelationList\Value $value
     */
    public function fromHash($hash)
    {
        return new Value($hash['destinationContentIds']);
    }

    /**
     * Converts a $Value to a hash.
     *
     * @param \Ibexa\Core\FieldType\RelationList\Value $value
     *
     * @return mixed
     */
    public function toHash(SPIValue $value)
    {
        return ['destinationContentIds' => $value->destinationContentIds];
    }

    /**
     * Returns whether the field type is searchable.
     *
     * @return bool
     */
    public function isSearchable(): bool
    {
        return true;
    }

    /**
     * Returns relation data extracted from value.
     *
     * Not intended for \Ibexa\Contracts\Core\Repository\Values\Content\Relation::COMMON type relations,
     * there is an API for handling those.
     *
     * @param \Ibexa\Core\FieldType\RelationList\Value $value
     *
     * @return array Hash with relation type as key and array of destination content ids as value.
     *
     * Example:
     * <code>
     *  array(
     *      \Ibexa\Contracts\Core\Repository\Values\Content\Relation::LINK => array(
     *          "contentIds" => array( 12, 13, 14 ),
     *          "locationIds" => array( 24 )
     *      ),
     *      \Ibexa\Contracts\Core\Repository\Values\Content\Relation::EMBED => array(
     *          "contentIds" => array( 12 ),
     *          "locationIds" => array( 24, 45 )
     *      ),
     *      \Ibexa\Contracts\Core\Repository\Values\Content\Relation::FIELD => array( 12 )
     *  )
     * </code>
     */
    public function getRelations(SPIValue $value)
    {
        /* @var \Ibexa\Core\FieldType\RelationList\Value $value */
        return [
            RelationType::FIELD->value => $value->destinationContentIds,
        ];
    }

    /**
     * Checks whether given selectionMethod is valid.
     *
     * @param int $selectionMethod
     *
     * @return bool
     */
    private function isValidSelectionMethod($selectionMethod): bool
    {
        return in_array($selectionMethod, [
            self::SELECTION_BROWSE,
            self::SELECTION_DROPDOWN,
            self::SELECTION_LIST_WITH_RADIO_BUTTONS,
            self::SELECTION_LIST_WITH_CHECKBOXES,
            self::SELECTION_MULTIPLE_SELECTION_LIST,
            self::SELECTION_TEMPLATE_BASED_MULTIPLE,
            self::SELECTION_TEMPLATE_BASED_SINGLE,
        ], true);
    }

    public static function getTranslationMessages(): array
    {
        return [
            Message::create('ibexa_object_relation_list.name', 'ibexa_fieldtypes')
                ->setDesc('Content relations (multiple)'),
        ];
    }
}
