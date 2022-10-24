<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
namespace Ibexa\Tests\Integration\Core\Repository\FieldType;

use Ibexa\Contracts\Core\Repository\Values\Content\Content;
use Ibexa\Contracts\Core\Repository\Values\Content\Field;
use Ibexa\Contracts\Core\Test\Repository\SetupFactory\Legacy;
use Ibexa\Core\Base\Exceptions\InvalidArgumentType;
use Ibexa\Core\FieldType\Relation\Value as RelationValue;
use Ibexa\Core\Repository\Values\Content\Relation;

/**
 * Integration test for use field type.
 *
 * @group integration
 * @group field-type
 */
class RelationIntegrationTest extends SearchBaseIntegrationTest
{
    use RelationSearchBaseIntegrationTestTrait;

    /**
     * Get name of tested field type.
     *
     * @return string
     */
    public function getTypeName()
    {
        return 'ezobjectrelation';
    }

    /**
     * {@inheritdoc}
     */
    protected function supportsLikeWildcard($value)
    {
        parent::supportsLikeWildcard($value);

        return false;
    }

    /**
     * @param \Ibexa\Contracts\Core\Repository\Values\Content\Content $content
     *
     * @return array|\Ibexa\Contracts\Core\Repository\Values\Content\Relation[]
     */
    public function getCreateExpectedRelations(Content $content)
    {
        $contentService = $this->getRepository()->getContentService();

        return [
            new Relation(
                [
                    'sourceFieldDefinitionIdentifier' => 'data',
                    'type' => Relation::FIELD,
                    'sourceContentInfo' => $content->contentInfo,
                    'destinationContentInfo' => $contentService->loadContentInfo(4),
                ]
            ),
        ];
    }

    /**
     * @param \Ibexa\Contracts\Core\Repository\Values\Content\Content $content
     *
     * @return array|\Ibexa\Contracts\Core\Repository\Values\Content\Relation[]
     */
    public function getUpdateExpectedRelations(Content $content)
    {
        $contentService = $this->getRepository()->getContentService();

        return [
            new Relation(
                [
                    'sourceFieldDefinitionIdentifier' => 'data',
                    'type' => Relation::FIELD,
                    'sourceContentInfo' => $content->contentInfo,
                    'destinationContentInfo' => $contentService->loadContentInfo(49),
                ]
            ),
        ];
    }

    public function getSettingsSchema()
    {
        return [
            'selectionMethod' => [
                'type' => 'int',
                'default' => 0,
            ],
            'selectionRoot' => [
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

    /**
     * @covers \Ibexa\Contracts\Core\Repository\Tests\FieldType\BaseIntegrationTest::getValidatorSchema()
     */
    public function getValidatorSchema()
    {
        return [];
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
            'selectionMethod' => 0,
            'selectionRoot' => 1,
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
        return [];
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
        return [
            'selectionMethod' => 'a',
            'selectionRoot' => true,
            'unknownSetting' => false,
            'selectionContentTypes' => true,
        ];
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
        return new RelationValue(4);
    }

    /**
     * Get name generated by the given field type (via fieldType->getName()).
     *
     * @return string
     */
    public function getFieldName()
    {
        return 'Users';
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
        $this->assertInstanceOf(
            RelationValue::class,
            $field->value
        );

        $expectedData = [
            'destinationContentId' => 4,
        ];
        $this->assertPropertiesCorrect(
            $expectedData,
            $field->value
        );
    }

    public function provideInvalidCreationFieldData()
    {
        return [
            [
                new RelationValue([]),
                InvalidArgumentType::class,
            ],
        ];
    }

    /**
     * Get update field externals data.
     *
     * @return array
     */
    public function getValidUpdateFieldData()
    {
        return new RelationValue(49);
    }

    /**
     * Get externals updated field data values.
     *
     * This is a PHPUnit data provider
     *
     * @return array
     */
    public function assertUpdatedFieldDataLoadedCorrect(Field $field)
    {
        self::assertInstanceOf(RelationValue::class, $field->value);

        $expectedData = [
            'destinationContentId' => 49,
        ];
        $this->assertPropertiesCorrect(
            $expectedData,
            $field->value
        );
    }

    public function provideInvalidUpdateFieldData()
    {
        return $this->provideInvalidCreationFieldData();
    }

    /**
     * Asserts the the field data was loaded correctly.
     *
     * Asserts that the data provided by {@link getValidCreationFieldData()}
     * was copied and loaded correctly.
     *
     * @param \Ibexa\Contracts\Core\Repository\Values\Content\Field $field
     */
    public function assertCopiedFieldDataLoadedCorrectly(Field $field)
    {
        $this->assertInstanceOf(
            RelationValue::class,
            $field->value
        );

        $expectedData = [
            'destinationContentId' => 4,
        ];

        $this->assertPropertiesCorrect(
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
    public function provideToHashData()
    {
        return [
            [
                new RelationValue(4),
                [
                    'destinationContentId' => 4,
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
    public function provideFromHashData()
    {
        return [
            [
                ['destinationContentId' => 4],
                new RelationValue(4),
            ],
        ];
    }

    public function providerForTestIsEmptyValue()
    {
        return [
            [new RelationValue()],
        ];
    }

    public function providerForTestIsNotEmptyValue()
    {
        return [
            [
                $this->getValidCreationFieldData(),
            ],
        ];
    }

    protected function getValidSearchValueOne()
    {
        // Using different values for Legacy Search Engine, in order to demonstrate that sort will
        // depend on how search engine stores field type's value. Legacy stores it as integer, while
        // other engines store it as string.
        if ($this->getSetupFactory() instanceof Legacy) {
            return 4;
        }

        return 10;
    }

    protected function getValidSearchValueTwo()
    {
        // Using different values for Legacy Search Engine, in order to demonstrate that sort will
        // depend on how search engine stores field type's value. Legacy stores it as integer, while
        // other engines store it as string.
        if ($this->getSetupFactory() instanceof Legacy) {
            return 49;
        }

        return 4;
    }
}

class_alias(RelationIntegrationTest::class, 'eZ\Publish\API\Repository\Tests\FieldType\RelationIntegrationTest');