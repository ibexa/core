<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Integration\Core\Repository\FieldType;

use Ibexa\Contracts\Core\Repository\Repository;
use Ibexa\Contracts\Core\Repository\Values\Content\Field;
use Ibexa\Contracts\Core\Repository\Values\Content\Query;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion;
use Ibexa\Contracts\Core\Repository\Values\ContentType\ContentType;
use Ibexa\Contracts\Core\Test\Repository\SetupFactory\Legacy;
use Ibexa\Core\Base\Exceptions\InvalidArgumentType;
use Ibexa\Core\FieldType\Checkbox\Value as CheckboxValue;

/**
 * Integration test for use field type.
 *
 * @group integration
 * @group field-type
 */
class CheckboxIntegrationTest extends SearchBaseIntegrationTestCase
{
    private const IS_ACTIVE_FIELD_DEF_IDENTIFIER = 'is_active';

    /**
     * Get name of tested field type.
     *
     * @return string
     */
    public function getTypeName(): string
    {
        return 'ibexa_boolean';
    }

    /**
     * Get expected settings schema.
     *
     * @return array
     */
    public function getSettingsSchema()
    {
        return [];
    }

    /**
     * Get a valid $fieldSettings value.
     *
     * @return mixed
     */
    public function getValidFieldSettings()
    {
        return [];
    }

    /**
     * Get $fieldSettings value not accepted by the field type.
     *
     * @return mixed
     */
    public function getInvalidFieldSettings()
    {
        return [
            'somethingUnknown' => 0,
        ];
    }

    /**
     * Get expected validator schema.
     *
     * @return array
     */
    public function getValidatorSchema()
    {
        return [];
    }

    /**
     * Get a valid $validatorConfiguration.
     *
     * @return mixed
     */
    public function getValidValidatorConfiguration()
    {
        return [];
    }

    /**
     * Get $validatorConfiguration not accepted by the field type.
     *
     * @return mixed
     */
    public function getInvalidValidatorConfiguration()
    {
        return [
            'unknown' => ['value' => 42],
        ];
    }

    /**
     * Get initial field data for valid object creation.
     *
     * @return mixed
     */
    public function getValidCreationFieldData()
    {
        return new CheckboxValue(true);
    }

    /**
     * Get name generated by the given field type (via fieldType->getName()).
     *
     * @return string
     */
    public function getFieldName(): string
    {
        return '1';
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
            CheckboxValue::class,
            $field->value
        );

        $expectedData = [
            'bool' => true,
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
                new CheckboxValue(new \stdClass()),
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
        return new CheckboxValue(false);
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
        self::assertInstanceOf(
            CheckboxValue::class,
            $field->value
        );

        $expectedData = [
            'bool' => false,
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
        self::assertInstanceOf(
            CheckboxValue::class,
            $field->value
        );

        $expectedData = [
            'bool' => true,
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
                new CheckboxValue(true),
                '1',
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
                '1',
                new CheckboxValue(true),
            ],
        ];
    }

    public function providerForTestIsEmptyValue()
    {
        return [];
    }

    public function providerForTestIsNotEmptyValue()
    {
        return [
            [$this->getValidCreationFieldData()],
            [new CheckboxValue(true)],
            [new CheckboxValue()],
            [new CheckboxValue(null)],
            [new CheckboxValue(false)],
        ];
    }

    protected function getValidSearchValueOne(): bool
    {
        return false;
    }

    protected function getValidSearchValueTwo(): bool
    {
        return true;
    }

    protected function getSearchTargetValueOne()
    {
        // Handling Legacy Search Engine, which stores Checkbox value as integer
        if ($this->getSetupFactory() instanceof Legacy) {
            return (int)$this->getValidSearchValueOne();
        }

        return parent::getSearchTargetValueOne();
    }

    protected function getSearchTargetValueTwo()
    {
        // Handling Legacy Search Engine, which stores Checkbox value as integer
        if ($this->getSetupFactory() instanceof Legacy) {
            return (int)$this->getValidSearchValueTwo();
        }

        return parent::getSearchTargetValueTwo();
    }

    /**
     * Data corresponds to Content items created by {@see createCheckboxContentItems}.
     */
    public function getDataForTestFindContentFieldCriterion(): iterable
    {
        // there are 2 Content items created, one with is_active = true, the other one with is_active = false
        yield 'active' => [true];
        yield 'not active' => [false];
    }

    /**
     * @dataProvider getDataForTestFindContentFieldCriterion
     *
     * @covers \Ibexa\Contracts\Core\Repository\SearchService::findContent
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\ForbiddenException
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\UnauthorizedException
     */
    public function testFindContentFieldCriterion(bool $isActive): void
    {
        $repository = $this->getRepository();
        $this->createCheckboxContentItems($repository);

        $criterion = new Criterion\Field(
            self::IS_ACTIVE_FIELD_DEF_IDENTIFIER,
            Criterion\Operator::EQ,
            $isActive
        );
        $query = new Query(['query' => $criterion]);

        $searchService = $repository->getSearchService();
        $searchResult = $searchService->findContent($query);

        self::assertEquals(1, $searchResult->totalCount);
        $contentItem = $searchResult->searchHits[0]->valueObject;
        /** @var \Ibexa\Contracts\Core\Repository\Values\Content\Content $contentItem */
        $value = $contentItem->getField('is_active')->value;
        /** @var \Ibexa\Core\FieldType\Checkbox\Value $value */
        self::assertSame($isActive, $value->bool);
    }

    public function testAddFieldDefinition(): void
    {
        $fieldTypeService = $this->getRepository()->getFieldTypeService();
        $content = $this->addFieldDefinition();

        self::assertCount(2, $content->getFields());
        self::assertFalse(
            $fieldTypeService
                ->getFieldType($this->getTypeName())
                ->isEmptyValue($content->getFieldValue('data'))
        );
    }

    /**
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\ForbiddenException
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\UnauthorizedException
     */
    protected function createCheckboxContentItems(Repository $repository): void
    {
        $contentType = $this->createContentTypeWithCheckboxField($repository);

        $contentService = $repository->getContentService();

        $toCreate = [
            'content-checkbox-active' => true,
            'content-checkbox-not-active' => false,
        ];
        foreach ($toCreate as $remoteId => $isActive) {
            $createStruct = $contentService->newContentCreateStruct($contentType, 'eng-GB');
            $createStruct->remoteId = $remoteId;
            $createStruct->alwaysAvailable = false;
            $createStruct->setField(self::IS_ACTIVE_FIELD_DEF_IDENTIFIER, $isActive);

            $contentService->publishVersion(
                $contentService->createContent($createStruct)->getVersionInfo()
            );
        }

        $this->refreshSearch($repository);
    }

    /**
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\ForbiddenException
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\UnauthorizedException
     */
    private function createContentTypeWithCheckboxField(Repository $repository): ContentType
    {
        $contentTypeService = $repository->getContentTypeService();

        $createStruct = $contentTypeService->newContentTypeCreateStruct('content-checkbox');
        $createStruct->mainLanguageCode = 'eng-GB';
        $createStruct->names = ['eng-GB' => 'Checkboxes'];

        $fieldCreate = $contentTypeService->newFieldDefinitionCreateStruct(
            self::IS_ACTIVE_FIELD_DEF_IDENTIFIER,
            'ibexa_boolean'
        );
        $fieldCreate->names = ['eng-GB' => 'Active'];
        $fieldCreate->position = 1;
        $fieldCreate->isTranslatable = false;
        $fieldCreate->isSearchable = true;

        $createStruct->addFieldDefinition($fieldCreate);

        $contentGroup = $contentTypeService->loadContentTypeGroupByIdentifier('Content');
        $contentTypeDraft = $contentTypeService->createContentType($createStruct, [$contentGroup]);
        $contentTypeService->publishContentTypeDraft($contentTypeDraft);

        return $contentTypeService->loadContentType($contentTypeDraft->id);
    }
}
