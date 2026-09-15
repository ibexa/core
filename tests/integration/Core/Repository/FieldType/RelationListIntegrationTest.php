<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Integration\Core\Repository\FieldType;

use Ibexa\Contracts\Core\Repository\Values\Content\Content;
use Ibexa\Contracts\Core\Repository\Values\Content\Field;
use Ibexa\Contracts\Core\Repository\Values\Content\RelationType;
use Ibexa\Core\Base\Exceptions\InvalidArgumentType;
use Ibexa\Core\FieldType\RelationList\Type as RelationListType;
use Ibexa\Core\FieldType\RelationList\Value as RelationListValue;
use Ibexa\Core\Repository\Values\Content\Relation;

/**
 * Integration test for use field type.
 */
#[\PHPUnit\Framework\Attributes\Group('integration')]
#[\PHPUnit\Framework\Attributes\Group('field-type')]
class RelationListIntegrationTest extends SearchMultivaluedBaseIntegrationTestCase
{
    use RelationSearchBaseIntegrationTestTrait;

    /**
     * Get name of tested field type.
     *
     * @return string
     */
    public function getTypeName(): string
    {
        return 'ibexa_object_relation_list';
    }

    /**
     * {@inheritdoc}
     */
    protected function supportsLikeWildcard($value): bool
    {
        return false;
    }

    public function getCreateExpectedRelations(Content $content): array
    {
        $contentService = $this->getRepository()->getContentService();

        return [
            new Relation(
                [
                    'sourceFieldDefinitionIdentifier' => 'data',
                    'type' => RelationType::FIELD->value,
                    'sourceContentInfo' => $content->contentInfo,
                    'destinationContentInfo' => $contentService->loadContentInfo(4),
                ]
            ),
            new Relation(
                [
                    'sourceFieldDefinitionIdentifier' => 'data',
                    'type' => RelationType::FIELD->value,
                    'sourceContentInfo' => $content->contentInfo,
                    'destinationContentInfo' => $contentService->loadContentInfo(49),
                ]
            ),
        ];
    }

    public function getUpdateExpectedRelations(Content $content): array
    {
        $contentService = $this->getRepository()->getContentService();

        return [
            new Relation(
                [
                    'sourceFieldDefinitionIdentifier' => 'data',
                    'type' => RelationType::FIELD->value,
                    'sourceContentInfo' => $content->contentInfo,
                    'destinationContentInfo' => $contentService->loadContentInfo(4),
                ]
            ),
            new Relation(
                [
                    'sourceFieldDefinitionIdentifier' => 'data',
                    'type' => RelationType::FIELD->value,
                    'sourceContentInfo' => $content->contentInfo,
                    'destinationContentInfo' => $contentService->loadContentInfo(49),
                ]
            ),
            new Relation(
                [
                    'sourceFieldDefinitionIdentifier' => 'data',
                    'type' => RelationType::FIELD->value,
                    'sourceContentInfo' => $content->contentInfo,
                    'destinationContentInfo' => $contentService->loadContentInfo(54),
                ]
            ),
        ];
    }

    public function getSettingsSchema()
    {
        return [
            'selectionMethod' => [
                'type' => 'int',
                'default' => RelationListType::SELECTION_BROWSE,
            ],
            'selectionDefaultLocation' => [
                'type' => 'string',
                'default' => null,
            ],
            'selectionContentTypes' => [
                'type' => 'array',
                'default' => [],
            ],
            'rootDefaultLocation' => [
                'type' => 'bool',
                'default' => false,
            ],
        ];
    }

    public function getValidatorSchema()
    {
        return [
            'RelationListValueValidator' => [
                'selectionLimit' => [
                    'type' => 'int',
                    'default' => 0,
                ],
            ],
        ];
    }

    /**
     * Get a valid $fieldSettings value.
     *
     * @todo Implement correctly
     *
     * @return mixed
     */
    public function getValidFieldSettings()
    {
        return [
            'selectionMethod' => 1,
            'selectionDefaultLocation' => 2,
            'selectionContentTypes' => [],
            'rootDefaultLocation' => false,
        ];
    }

    /**
     * Get a valid $validatorConfiguration.
     *
     * @todo Implement correctly
     *
     * @return mixed
     */
    public function getValidValidatorConfiguration()
    {
        return [
            'RelationListValueValidator' => [
                'selectionLimit' => 0,
            ],
        ];
    }

    /**
     * Get $fieldSettings value not accepted by the field type.
     *
     * @todo Implement correctly
     *
     * @return mixed
     */
    public function getInvalidFieldSettings()
    {
        return ['selectionMethod' => 'a', 'selectionDefaultLocation' => true, 'unknownSetting' => false];
    }

    /**
     * Get $validatorConfiguration not accepted by the field type.
     *
     * @todo Implement correctly
     *
     * @return mixed
     */
    public function getInvalidValidatorConfiguration()
    {
        return ['noValidator' => true];
    }

    /**
     * Get initial field data for valid object creation.
     *
     * @return mixed
     */
    public function getValidCreationFieldData()
    {
        return new RelationListValue([4, 49]);
    }

    /**
     * Get name generated by the given field type (via fieldType->getName()).
     *
     * @return string
     */
    public function getFieldName(): string
    {
        return 'Users' . ' ' . 'Images';
    }

    /**
     * Asserts that the field data was loaded correctly.
     *
     * Asserts that the data provided by {@link getValidCreationFieldData()}
     * was stored and loaded correctly.
     *
     * @param \Ibexa\Contracts\Core\Repository\Values\Content\Field $field
     */
    public function assertFieldDataLoadedCorrect(Field $field)
    {
        self::assertInstanceOf(
            RelationListValue::class,
            $field->value
        );

        $expectedData = [
            'destinationContentIds' => [4, 49],
        ];
        $this->assertPropertiesCorrectUnsorted(
            $expectedData,
            $field->value
        );
    }

    public static function provideInvalidCreationFieldData()
    {
        return [
            [
                new RelationListValue([null]),
                InvalidArgumentType::class,
            ],
        ];
    }

    /**
     * Get update field externals data.
     */
    public function getValidUpdateFieldData(): RelationListValue
    {
        return new RelationListValue([49, 54, 4]);
    }

    /**
     * Get externals updated field data values.
     *
     * This is a PHPUnit data provider
     */
    public function assertUpdatedFieldDataLoadedCorrect(Field $field): void
    {
        self::assertInstanceOf(RelationListValue::class, $field->value);

        $expectedData = [
            'destinationContentIds' => [49, 54, 4],
        ];
        $this->assertPropertiesCorrectUnsorted(
            $expectedData,
            $field->value
        );
    }

    public static function provideInvalidUpdateFieldData()
    {
        return self::provideInvalidCreationFieldData();
    }

    /**
     * Asserts the the field data was loaded correctly.
     *
     * Asserts that the data provided by {@link getValidCreationFieldData()}
     * was copied and loaded correctly.
     *
     * @param \Ibexa\Contracts\Core\Repository\Values\Content\Field $field
     */
    public function assertCopiedFieldDataLoadedCorrectly(Field $field): void
    {
        self::assertInstanceOf(
            RelationListValue::class,
            $field->value
        );

        $expectedData = [
            'destinationContentIds' => [4, 49],
        ];
        $this->assertPropertiesCorrectUnsorted(
            $expectedData,
            $field->value
        );
    }

    /**
     * Get data to test to hash method.
     *
     * This is a PHPUnit data provider
     *
     * The returned records must have the the original value assigned to the
     * first index and the expected hash result to the second. For example:
     *
     * <code>
     * array(
     *      array(
     *          new MyValue( true ),
     *          array( 'myValue' => true ),
     *      ),
     *      // ...
     * );
     * </code>
     *
     * @return array
     */
    public static function provideToHashData()
    {
        return [
            [
                new RelationListValue([4, 49]),
                [
                    'destinationContentIds' => [4, 49],
                ],
            ],
        ];
    }

    /**
     * Get expectations for the fromHash call on our field value.
     *
     * This is a PHPUnit data provider
     *
     * @return array
     */
    public static function provideFromHashData()
    {
        return [
            [
                ['destinationContentIds' => [4, 49]],
                new RelationListValue([4, 49]),
            ],
        ];
    }

    public static function providerForTestIsEmptyValue()
    {
        return [
            [new RelationListValue()],
            [new RelationListValue([])],
        ];
    }

    public static function providerForTestIsNotEmptyValue()
    {
        return [
            [
                new RelationListValue([4, 49]),
            ],
        ];
    }

    protected static function getValidSearchValueOne()
    {
        return [11];
    }

    protected static function getValidSearchValueTwo()
    {
        return [12];
    }

    protected static function getSearchTargetValueOne(): int
    {
        return 11;
    }

    protected static function getSearchTargetValueTwo(): int
    {
        return 12;
    }

    protected static function getValidMultivaluedSearchValuesOne()
    {
        return [11, 12];
    }

    protected static function getValidMultivaluedSearchValuesTwo()
    {
        return [13, 14];
    }
}
