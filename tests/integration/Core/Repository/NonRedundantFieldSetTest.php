<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Integration\Core\Repository;

use Ibexa\Contracts\Core\Repository\Values\Content\Content;
use Ibexa\Contracts\Core\Repository\Values\Content\VersionInfo;
use Ibexa\Core\Repository\ContentService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Depends;
use PHPUnit\Framework\Attributes\DependsExternal;
use PHPUnit\Framework\Attributes\Group;

/**
 * Test case for create and update Content operations in the ContentService with regard to
 * non-redundant set of fields being passed to the storage.
 *
 * These tests depends on TextLine field type being functional.
 */
#[CoversClass(ContentService::class)]
#[Group('content')]
class NonRedundantFieldSetTest extends BaseNonRedundantFieldSetTestCase
{
    /**
     * Test for the createContent() method.
     *
     * Default values are stored.
     *
     *
     *
     * @return \Ibexa\Contracts\Core\Repository\Values\Content\Content
     */
    #[DependsExternal(ContentServiceTest::class, 'testCreateContent')]
    #[DependsExternal(ContentTypeServiceTest::class, 'testCreateContentType')]
    public function testCreateContentDefaultValues()
    {
        $mainLanguageCode = 'eng-US';
        $fieldValues = [
            'field1' => ['eng-US' => 'new value 1'],
            'field3' => ['eng-US' => 'new value 3'],
        ];

        $content = $this->createTestContent($mainLanguageCode, $fieldValues);

        self::assertInstanceOf(Content::class, $content);

        return $content;
    }

    /**
     * Test for the createContent() method.
     *
     *
     *
     * @param \Ibexa\Contracts\Core\Repository\Values\Content\Content $content
     */
    #[Depends('testCreateContentDefaultValues')]
    public function testCreateContentDefaultValuesFields(Content $content)
    {
        self::assertCount(1, $content->versionInfo->languageCodes);
        self::assertContains('eng-US', $content->versionInfo->languageCodes);
        self::assertCount(4, $content->getFields());

        // eng-US
        self::assertEquals('new value 1', $content->getFieldValue('field1', 'eng-US'));
        self::assertEquals('default value 2', $content->getFieldValue('field2', 'eng-US'));
        self::assertEquals('new value 3', $content->getFieldValue('field3', 'eng-US'));
        self::assertEquals('default value 4', $content->getFieldValue('field4', 'eng-US'));
    }

    /**
     * Test for the createContent() method.
     *
     * Creating fields with empty values, no values being passed to storage.
     *
     *
     *
     * @return \Ibexa\Contracts\Core\Repository\Values\Content\Content
     */
    #[DependsExternal(ContentServiceTest::class, 'testCreateContent')]
    #[DependsExternal(ContentTypeServiceTest::class, 'testCreateContentType')]
    public function testCreateContentEmptyValues()
    {
        $mainLanguageCode = 'eng-US';
        $fieldValues = [
            'field2' => ['eng-US' => null],
            'field4' => ['eng-US' => null],
        ];

        $content = $this->createTestContent($mainLanguageCode, $fieldValues);

        self::assertInstanceOf(Content::class, $content);

        return $content;
    }

    /**
     * Test for the createContent() method.
     *
     *
     *
     * @param \Ibexa\Contracts\Core\Repository\Values\Content\Content $content
     */
    #[Depends('testCreateContentEmptyValues')]
    public function testCreateContentEmptyValuesFields(Content $content)
    {
        $emptyValue = $this->getRepository()->getFieldTypeService()->getFieldType('ibexa_string')->getEmptyValue();

        self::assertCount(1, $content->versionInfo->languageCodes);
        self::assertContains('eng-US', $content->versionInfo->languageCodes);
        self::assertCount(4, $content->getFields());

        // eng-US
        self::assertContains('eng-US', $content->versionInfo->languageCodes);
        self::assertEquals($emptyValue, $content->getFieldValue('field1', 'eng-US'));
        self::assertEquals($emptyValue, $content->getFieldValue('field2', 'eng-US'));
        self::assertEquals($emptyValue, $content->getFieldValue('field3', 'eng-US'));
        self::assertEquals($emptyValue, $content->getFieldValue('field4', 'eng-US'));
    }

    /**
     * Test for the createContent() method.
     *
     * Creating fields with empty values, no values being passed to storage.
     * Case where additional language is not stored.
     *
     *
     *
     * @return \Ibexa\Contracts\Core\Repository\Values\Content\Content
     */
    #[DependsExternal(ContentServiceTest::class, 'testCreateContent')]
    #[DependsExternal(ContentTypeServiceTest::class, 'testCreateContentType')]
    public function testCreateContentEmptyValuesTranslationNotStored()
    {
        $mainLanguageCode = 'eng-US';
        $fieldValues = [
            'field2' => ['eng-US' => null],
            'field4' => ['eng-US' => null, 'ger-DE' => null],
        ];

        $content = $this->createTestContent($mainLanguageCode, $fieldValues);

        self::assertInstanceOf(Content::class, $content);

        return $content;
    }

    /**
     * Test for the createContent() method.
     *
     *
     *
     * @param \Ibexa\Contracts\Core\Repository\Values\Content\Content $content
     */
    #[Depends('testCreateContentEmptyValuesTranslationNotStored')]
    public function testCreateContentEmptyValuesTranslationNotStoredFields(Content $content)
    {
        $emptyValue = $this->getRepository()->getFieldTypeService()->getFieldType('ibexa_string')->getEmptyValue();

        self::assertCount(1, $content->versionInfo->languageCodes);
        self::assertContains('eng-US', $content->versionInfo->languageCodes);
        self::assertCount(4, $content->getFields());

        // eng-US
        self::assertContains('eng-US', $content->versionInfo->languageCodes);
        self::assertEquals($emptyValue, $content->getFieldValue('field1', 'eng-US'));
        self::assertEquals($emptyValue, $content->getFieldValue('field2', 'eng-US'));
        self::assertEquals($emptyValue, $content->getFieldValue('field3', 'eng-US'));
        self::assertEquals($emptyValue, $content->getFieldValue('field4', 'eng-US'));

        // ger-DE is not stored!
        self::assertNotContains('ger-DE', $content->versionInfo->languageCodes);
    }

    /**
     * Test for the createContent() method.
     *
     * Creating with two languages, main language is always stored (even with all values being empty).
     *
     *
     *
     * @return \Ibexa\Contracts\Core\Repository\Values\Content\Content
     */
    #[DependsExternal(ContentServiceTest::class, 'testCreateContent')]
    #[DependsExternal(ContentTypeServiceTest::class, 'testCreateContentType')]
    public function testCreateContentTwoLanguagesMainTranslationStored()
    {
        $mainLanguageCode = 'eng-US';
        $fieldValues = [
            'field2' => ['eng-US' => null],
            'field4' => ['eng-US' => null, 'ger-DE' => 'new ger-DE value 4'],
        ];

        $content = $this->createTestContent($mainLanguageCode, $fieldValues);

        self::assertInstanceOf(Content::class, $content);

        return $content;
    }

    /**
     * Test for the createContent() method.
     *
     *
     *
     * @param \Ibexa\Contracts\Core\Repository\Values\Content\Content $content
     */
    #[Depends('testCreateContentTwoLanguagesMainTranslationStored')]
    public function testCreateContentTwoLanguagesMainTranslationStoredFields(Content $content)
    {
        $emptyValue = $this->getRepository()->getFieldTypeService()->getFieldType('ibexa_string')->getEmptyValue();

        self::assertCount(2, $content->versionInfo->languageCodes);
        self::assertContains('ger-DE', $content->versionInfo->languageCodes);
        self::assertContains('eng-US', $content->versionInfo->languageCodes);
        self::assertCount(8, $content->getFields());

        // eng-US
        self::assertContains('eng-US', $content->versionInfo->languageCodes);
        self::assertEquals($emptyValue, $content->getFieldValue('field1', 'eng-US'));
        self::assertEquals($emptyValue, $content->getFieldValue('field2', 'eng-US'));
        self::assertEquals($emptyValue, $content->getFieldValue('field3', 'eng-US'));
        self::assertEquals($emptyValue, $content->getFieldValue('field4', 'eng-US'));

        // ger-DE
        self::assertEquals($emptyValue, $content->getFieldValue('field1', 'ger-DE'));
        self::assertEquals($emptyValue, $content->getFieldValue('field2', 'ger-DE'));
        self::assertEquals($emptyValue, $content->getFieldValue('field3', 'ger-DE'));
        self::assertEquals('new ger-DE value 4', $content->getFieldValue('field4', 'ger-DE'));
    }

    /**
     * Test for the createContent() method.
     *
     * Creating with two languages, second (not main one) language with empty values, causing no fields
     * for it being passed to the storage. Second language will not be stored.
     *
     *
     *
     * @return \Ibexa\Contracts\Core\Repository\Values\Content\Content
     */
    #[DependsExternal(ContentServiceTest::class, 'testCreateContent')]
    #[DependsExternal(ContentTypeServiceTest::class, 'testCreateContentType')]
    public function testCreateContentTwoLanguagesSecondTranslationNotStored()
    {
        $mainLanguageCode = 'eng-US';
        $fieldValues = [
            'field4' => ['ger-DE' => null],
        ];

        $content = $this->createTestContent($mainLanguageCode, $fieldValues);

        self::assertInstanceOf(Content::class, $content);

        return $content;
    }

    /**
     * Test for the createContent() method.
     *
     *
     *
     * @param \Ibexa\Contracts\Core\Repository\Values\Content\Content $content
     */
    #[Depends('testCreateContentTwoLanguagesSecondTranslationNotStored')]
    public function testCreateContentTwoLanguagesSecondTranslationNotStoredFields(Content $content)
    {
        $emptyValue = $this->getRepository()->getFieldTypeService()->getFieldType('ibexa_string')->getEmptyValue();

        self::assertCount(1, $content->versionInfo->languageCodes);
        self::assertContains('eng-US', $content->versionInfo->languageCodes);
        self::assertCount(4, $content->getFields());

        // eng-US
        self::assertEquals($emptyValue, $content->getFieldValue('field1', 'eng-US'));
        self::assertEquals('default value 2', $content->getFieldValue('field2', 'eng-US'));
        self::assertEquals($emptyValue, $content->getFieldValue('field3', 'eng-US'));
        self::assertEquals('default value 4', $content->getFieldValue('field4', 'eng-US'));

        // ger-DE is not stored!
        self::assertNotContains('ger-DE', $content->versionInfo->languageCodes);
    }

    /**
     * Test for the createContent() method.
     *
     * Creating with no fields in struct, using only default values.
     *
     *
     *
     * @return \Ibexa\Contracts\Core\Repository\Values\Content\Content
     */
    #[DependsExternal(ContentServiceTest::class, 'testCreateContent')]
    #[DependsExternal(ContentTypeServiceTest::class, 'testCreateContentType')]
    public function testCreateContentDefaultValuesNoStructFields()
    {
        $mainLanguageCode = 'eng-US';
        $fieldValues = [];

        $content = $this->createTestContent($mainLanguageCode, $fieldValues);

        self::assertInstanceOf(Content::class, $content);

        return $content;
    }

    /**
     * Test for the createContent() method.
     *
     *
     *
     * @param \Ibexa\Contracts\Core\Repository\Values\Content\Content $content
     */
    #[Depends('testCreateContentDefaultValuesNoStructFields')]
    public function testCreateContentDefaultValuesNoStructFieldsFields(Content $content)
    {
        $emptyValue = $this->getRepository()->getFieldTypeService()->getFieldType('ibexa_string')->getEmptyValue();

        self::assertCount(1, $content->versionInfo->languageCodes);
        self::assertContains('eng-US', $content->versionInfo->languageCodes);
        self::assertCount(4, $content->getFields());

        // eng-US
        self::assertEquals($emptyValue, $content->getFieldValue('field1', 'eng-US'));
        self::assertEquals('default value 2', $content->getFieldValue('field2', 'eng-US'));
        self::assertEquals($emptyValue, $content->getFieldValue('field3', 'eng-US'));
        self::assertEquals('default value 4', $content->getFieldValue('field4', 'eng-US'));
    }

    /**
     * Test for the createContent() method.
     *
     * Creating in two languages with no given field values for main language.
     *
     *
     *
     * @return \Ibexa\Contracts\Core\Repository\Values\Content\Content
     */
    #[DependsExternal(ContentServiceTest::class, 'testCreateContent')]
    #[DependsExternal(ContentTypeServiceTest::class, 'testCreateContentType')]
    public function testCreateContentTwoLanguagesNoValuesForMainLanguage()
    {
        $mainLanguageCode = 'eng-US';
        $fieldValues = [
            'field4' => ['ger-DE' => 'new value 4'],
        ];

        $content = $this->createTestContent($mainLanguageCode, $fieldValues);

        self::assertInstanceOf(Content::class, $content);

        return $content;
    }

    /**
     * Test for the createContent() method.
     *
     *
     *
     * @param \Ibexa\Contracts\Core\Repository\Values\Content\Content $content
     */
    #[Depends('testCreateContentTwoLanguagesNoValuesForMainLanguage')]
    public function testCreateContentTwoLanguagesNoValuesForMainLanguageFields(Content $content)
    {
        $emptyValue = $this->getRepository()->getFieldTypeService()->getFieldType('ibexa_string')->getEmptyValue();

        self::assertCount(2, $content->versionInfo->languageCodes);
        self::assertContains('ger-DE', $content->versionInfo->languageCodes);
        self::assertContains('eng-US', $content->versionInfo->languageCodes);
        self::assertCount(8, $content->getFields());

        // eng-US
        self::assertEquals($emptyValue, $content->getFieldValue('field1', 'eng-US'));
        self::assertEquals('default value 2', $content->getFieldValue('field2', 'eng-US'));
        self::assertEquals($emptyValue, $content->getFieldValue('field3', 'eng-US'));
        self::assertEquals('default value 4', $content->getFieldValue('field4', 'eng-US'));

        // ger-DE
        self::assertEquals($emptyValue, $content->getFieldValue('field1', 'ger-DE'));
        self::assertEquals('default value 2', $content->getFieldValue('field2', 'ger-DE'));
        self::assertEquals($emptyValue, $content->getFieldValue('field3', 'ger-DE'));
        self::assertEquals('new value 4', $content->getFieldValue('field4', 'ger-DE'));
    }

    /**
     * Test for the createContentDraft() method.
     *
     *
     *
     * @return \Ibexa\Contracts\Core\Repository\Values\Content\Content[]
     */
    #[Depends('testCreateContentTwoLanguagesMainTranslationStoredFields')]
    public function testCreateContentDraft()
    {
        $repository = $this->getRepository();
        $contentService = $repository->getContentService();

        $draft = $this->createMultilingualTestContent();
        $published = $contentService->publishVersion($draft->versionInfo);
        $newDraft = $contentService->createContentDraft($published->contentInfo);

        $newDraft = $contentService->loadContent($newDraft->id, null, $newDraft->versionInfo->versionNo);

        return [$published, $newDraft];
    }

    /**
     * Test for the createContentDraft() method.
     *
     *
     * @param \Ibexa\Contracts\Core\Repository\Values\Content\Content[] $data
     */
    #[Depends('testCreateContentDraft')]
    public function testCreateContentDraftFields(array $data)
    {
        $content = $data[1];

        self::assertEquals(VersionInfo::STATUS_DRAFT, $content->versionInfo->status);
        self::assertEquals(2, $content->versionInfo->versionNo);
        self::assertCount(2, $content->versionInfo->languageCodes);
        self::assertContains('eng-US', $content->versionInfo->languageCodes);
        self::assertContains('eng-GB', $content->versionInfo->languageCodes);
        self::assertCount(8, $content->getFields());

        // eng-US
        self::assertEquals('value 1', $content->getFieldValue('field1', 'eng-US'));
        self::assertEquals('value 2', $content->getFieldValue('field2', 'eng-US'));
        self::assertEquals('value 3', $content->getFieldValue('field3', 'eng-US'));
        self::assertEquals('value 4', $content->getFieldValue('field4', 'eng-US'));

        // eng-GB
        self::assertEquals('value 1', $content->getFieldValue('field1', 'eng-GB'));
        self::assertEquals('value 2', $content->getFieldValue('field2', 'eng-GB'));
        self::assertEquals('value 3 eng-GB', $content->getFieldValue('field3', 'eng-GB'));
        self::assertEquals('value 4 eng-GB', $content->getFieldValue('field4', 'eng-GB'));
    }

    /**
     * Test for the createContentDraft() method.
     *
     *
     * @param \Ibexa\Contracts\Core\Repository\Values\Content\Content[] $data
     */
    #[Depends('testCreateContentDraft')]
    #[Depends('testCreateContentDraftFields')]
    public function testCreateContentDraftFieldsRetainsIds(array $data)
    {
        $this->assertFieldIds($data[0], $data[1]);
    }

    /**
     * Test for the updateContent() method.
     *
     * Testing update with new language:
     *  - value for new language is copied from value in main language
     *  - value for new language is empty
     *  - value for new language is not empty
     *
     *
     *
     * @return \Ibexa\Contracts\Core\Repository\Values\Content\Content
     */
    #[DependsExternal(ContentServiceTest::class, 'testCreateContent')]
    #[DependsExternal(ContentTypeServiceTest::class, 'testCreateContentType')]
    public function testUpdateContentWithNewLanguage()
    {
        $initialLanguageCode = 'ger-DE';
        $fieldValues = [
            'field4' => ['ger-DE' => 'new value 4'],
        ];

        $content = $this->updateTestContent($initialLanguageCode, $fieldValues);
        self::assertInstanceOf(Content::class, $content);

        return $content;
    }

    /**
     * Test for the updateContent() method.
     *
     *
     *
     * @param \Ibexa\Contracts\Core\Repository\Values\Content\Content $content
     */
    #[Depends('testUpdateContentWithNewLanguage')]
    public function testUpdateContentWithNewLanguageFields(Content $content)
    {
        $emptyValue = $this->getRepository()->getFieldTypeService()->getFieldType('ibexa_string')->getEmptyValue();

        self::assertCount(3, $content->versionInfo->languageCodes);
        self::assertContains('ger-DE', $content->versionInfo->languageCodes);
        self::assertContains('eng-US', $content->versionInfo->languageCodes);
        self::assertContains('eng-GB', $content->versionInfo->languageCodes);
        self::assertCount(12, $content->getFields());

        // eng-US
        self::assertEquals('value 1', $content->getFieldValue('field1', 'eng-US'));
        self::assertEquals('value 2', $content->getFieldValue('field2', 'eng-US'));
        self::assertEquals('value 3', $content->getFieldValue('field3', 'eng-US'));
        self::assertEquals('value 4', $content->getFieldValue('field4', 'eng-US'));

        // eng-GB
        self::assertEquals('value 1', $content->getFieldValue('field1', 'eng-GB'));
        self::assertEquals('value 2', $content->getFieldValue('field2', 'eng-GB'));
        self::assertEquals('value 3 eng-GB', $content->getFieldValue('field3', 'eng-GB'));
        self::assertEquals('value 4 eng-GB', $content->getFieldValue('field4', 'eng-GB'));

        // ger-DE
        self::assertEquals('value 1', $content->getFieldValue('field1', 'ger-DE'));
        self::assertEquals('value 2', $content->getFieldValue('field2', 'ger-DE'));
        self::assertEquals($emptyValue, $content->getFieldValue('field3', 'ger-DE'));
        self::assertEquals('new value 4', $content->getFieldValue('field4', 'ger-DE'));
    }

    /**
     * Test for the updateContent() method.
     *
     * Testing update of existing language and adding a new language:
     *  - value for new language is copied from value in main language
     *  - value for new language is empty
     *  - value for new language is not empty
     *  - existing language value updated with empty value
     *  - existing language value not changed
     *
     *
     *
     * @return \Ibexa\Contracts\Core\Repository\Values\Content\Content
     */
    #[DependsExternal(ContentServiceTest::class, 'testCreateContent')]
    #[DependsExternal(ContentTypeServiceTest::class, 'testCreateContentType')]
    public function testUpdateContentWithNewLanguageVariant()
    {
        $initialLanguageCode = 'ger-DE';
        $fieldValues = [
            'field1' => ['eng-US' => null],
            'field4' => ['ger-DE' => 'new value 4'],
        ];

        $content = $this->updateTestContent($initialLanguageCode, $fieldValues);
        self::assertInstanceOf(Content::class, $content);

        return $content;
    }

    /**
     * Test for the updateContent() method.
     *
     *
     *
     * @param \Ibexa\Contracts\Core\Repository\Values\Content\Content $content
     */
    #[Depends('testUpdateContentWithNewLanguageVariant')]
    public function testUpdateContentWithNewLanguageVariantFields(Content $content)
    {
        $emptyValue = $this->getRepository()->getFieldTypeService()->getFieldType('ibexa_string')->getEmptyValue();

        self::assertCount(3, $content->versionInfo->languageCodes);
        self::assertContains('ger-DE', $content->versionInfo->languageCodes);
        self::assertContains('eng-US', $content->versionInfo->languageCodes);
        self::assertContains('eng-GB', $content->versionInfo->languageCodes);
        self::assertCount(12, $content->getFields());

        // eng-US
        self::assertEquals($emptyValue, $content->getFieldValue('field1', 'eng-US'));
        self::assertEquals('value 2', $content->getFieldValue('field2', 'eng-US'));
        self::assertEquals('value 3', $content->getFieldValue('field3', 'eng-US'));
        self::assertEquals('value 4', $content->getFieldValue('field4', 'eng-US'));

        // eng-GB
        self::assertEquals($emptyValue, $content->getFieldValue('field1', 'eng-GB'));
        self::assertEquals('value 2', $content->getFieldValue('field2', 'eng-GB'));
        self::assertEquals('value 3 eng-GB', $content->getFieldValue('field3', 'eng-GB'));
        self::assertEquals('value 4 eng-GB', $content->getFieldValue('field4', 'eng-GB'));

        // ger-DE
        self::assertEquals($emptyValue, $content->getFieldValue('field1', 'ger-DE'));
        self::assertEquals('value 2', $content->getFieldValue('field2', 'ger-DE'));
        self::assertEquals($emptyValue, $content->getFieldValue('field3', 'ger-DE'));
        self::assertEquals('new value 4', $content->getFieldValue('field4', 'ger-DE'));
    }

    /**
     * Test for the updateContent() method.
     *
     * Updating with with new language and no field values given in the update struct.
     *
     *
     *
     * @return \Ibexa\Contracts\Core\Repository\Values\Content\Content
     */
    #[DependsExternal(ContentServiceTest::class, 'testCreateContent')]
    #[DependsExternal(ContentTypeServiceTest::class, 'testCreateContentType')]
    public function testUpdateContentWithNewLanguageNoValues()
    {
        $initialLanguageCode = 'ger-DE';
        $fieldValues = [];

        $content = $this->updateTestContent($initialLanguageCode, $fieldValues);
        self::assertInstanceOf(Content::class, $content);

        return $content;
    }

    /**
     * Test for the updateContent() method.
     *
     *
     *
     * @param \Ibexa\Contracts\Core\Repository\Values\Content\Content $content
     */
    #[Depends('testUpdateContentWithNewLanguageNoValues')]
    public function testUpdateContentWithNewLanguageNoValuesFields(Content $content)
    {
        $emptyValue = $this->getRepository()->getFieldTypeService()->getFieldType('ibexa_string')->getEmptyValue();

        self::assertCount(3, $content->versionInfo->languageCodes);
        self::assertContains('ger-DE', $content->versionInfo->languageCodes);
        self::assertContains('eng-US', $content->versionInfo->languageCodes);
        self::assertContains('eng-GB', $content->versionInfo->languageCodes);
        self::assertCount(12, $content->getFields());

        // eng-US
        self::assertEquals('value 1', $content->getFieldValue('field1', 'eng-US'));
        self::assertEquals('value 2', $content->getFieldValue('field2', 'eng-US'));
        self::assertEquals('value 3', $content->getFieldValue('field3', 'eng-US'));
        self::assertEquals('value 4', $content->getFieldValue('field4', 'eng-US'));

        // eng-GB
        self::assertEquals('value 1', $content->getFieldValue('field1', 'eng-GB'));
        self::assertEquals('value 2', $content->getFieldValue('field2', 'eng-GB'));
        self::assertEquals('value 3 eng-GB', $content->getFieldValue('field3', 'eng-GB'));
        self::assertEquals('value 4 eng-GB', $content->getFieldValue('field4', 'eng-GB'));

        // ger-DE
        self::assertEquals('value 1', $content->getFieldValue('field1', 'ger-DE'));
        self::assertEquals('value 2', $content->getFieldValue('field2', 'ger-DE'));
        self::assertEquals($emptyValue, $content->getFieldValue('field3', 'ger-DE'));
        self::assertEquals('default value 4', $content->getFieldValue('field4', 'ger-DE'));
    }

    /**
     * Test for the updateContent() method.
     *
     * When updating Content with two languages, updating non-translatable field will also update it's value
     * for non-main language.
     *
     *
     *
     * @return \Ibexa\Contracts\Core\Repository\Values\Content\Content
     */
    #[DependsExternal(ContentServiceTest::class, 'testCreateContent')]
    #[DependsExternal(ContentTypeServiceTest::class, 'testCreateContentType')]
    public function testUpdateContentUpdatingNonTranslatableFieldUpdatesFieldCopy()
    {
        $initialLanguageCode = 'eng-US';
        $fieldValues = [
            'field1' => ['eng-US' => 'new value 1'],
            'field2' => ['eng-US' => null],
        ];

        $content = $this->updateTestContent($initialLanguageCode, $fieldValues);
        self::assertInstanceOf(Content::class, $content);

        return $content;
    }

    /**
     * Test for the updateContent() method.
     *
     *
     *
     * @param \Ibexa\Contracts\Core\Repository\Values\Content\Content $content
     */
    #[Depends('testUpdateContentUpdatingNonTranslatableFieldUpdatesFieldCopy')]
    public function testUpdateContentUpdatingNonTranslatableFieldUpdatesFieldCopyFields(Content $content)
    {
        $emptyValue = $this->getRepository()->getFieldTypeService()->getFieldType('ibexa_string')->getEmptyValue();

        self::assertCount(2, $content->versionInfo->languageCodes);
        self::assertContains('eng-US', $content->versionInfo->languageCodes);
        self::assertContains('eng-GB', $content->versionInfo->languageCodes);
        self::assertCount(8, $content->getFields());

        // eng-US
        self::assertEquals('new value 1', $content->getFieldValue('field1', 'eng-US'));
        self::assertEquals($emptyValue, $content->getFieldValue('field2', 'eng-US'));
        self::assertEquals('value 3', $content->getFieldValue('field3', 'eng-US'));
        self::assertEquals('value 4', $content->getFieldValue('field4', 'eng-US'));

        // eng-GB
        self::assertEquals('new value 1', $content->getFieldValue('field1', 'eng-GB'));
        self::assertEquals($emptyValue, $content->getFieldValue('field2', 'eng-GB'));
        self::assertEquals('value 3 eng-GB', $content->getFieldValue('field3', 'eng-GB'));
        self::assertEquals('value 4 eng-GB', $content->getFieldValue('field4', 'eng-GB'));
    }

    /**
     * Test for the updateContent() method.
     *
     * Updating with two languages, initial language is always stored (even with all values being empty).
     *
     *
     *
     * @return \Ibexa\Contracts\Core\Repository\Values\Content\Content
     */
    #[DependsExternal(ContentServiceTest::class, 'testCreateContent')]
    #[DependsExternal(ContentTypeServiceTest::class, 'testCreateContentType')]
    public function testUpdateContentWithTwoLanguagesInitialLanguageTranslationNotCreated()
    {
        $initialLanguageCode = 'ger-DE';
        $fieldValues = [
            'field1' => ['eng-US' => null],
            'field2' => ['eng-US' => null],
            'field4' => ['ger-DE' => null],
        ];

        $content = $this->updateTestContent($initialLanguageCode, $fieldValues);
        self::assertInstanceOf(Content::class, $content);

        return $content;
    }

    /**
     * Test for the updateContent() method.
     *
     *
     *
     * @param \Ibexa\Contracts\Core\Repository\Values\Content\Content $content
     */
    #[Depends('testUpdateContentWithTwoLanguagesInitialLanguageTranslationNotCreated')]
    public function testUpdateContentWithTwoLanguagesInitialLanguageTranslationNotCreatedFields(Content $content)
    {
        $emptyValue = $this->getRepository()->getFieldTypeService()->getFieldType('ibexa_string')->getEmptyValue();

        self::assertCount(3, $content->versionInfo->languageCodes);
        self::assertContains('ger-DE', $content->versionInfo->languageCodes);
        self::assertContains('eng-US', $content->versionInfo->languageCodes);
        self::assertContains('eng-GB', $content->versionInfo->languageCodes);
        self::assertCount(12, $content->getFields());

        // eng-US
        self::assertEquals($emptyValue, $content->getFieldValue('field1', 'eng-US'));
        self::assertEquals($emptyValue, $content->getFieldValue('field2', 'eng-US'));
        self::assertEquals('value 3', $content->getFieldValue('field3', 'eng-US'));
        self::assertEquals('value 4', $content->getFieldValue('field4', 'eng-US'));

        // eng-GB
        self::assertEquals($emptyValue, $content->getFieldValue('field1', 'eng-GB'));
        self::assertEquals($emptyValue, $content->getFieldValue('field2', 'eng-GB'));
        self::assertEquals('value 3 eng-GB', $content->getFieldValue('field3', 'eng-GB'));
        self::assertEquals('value 4 eng-GB', $content->getFieldValue('field4', 'eng-GB'));

        // ger-DE
        self::assertEquals($emptyValue, $content->getFieldValue('field1', 'ger-DE'));
        self::assertEquals($emptyValue, $content->getFieldValue('field2', 'ger-DE'));
        self::assertEquals($emptyValue, $content->getFieldValue('field3', 'ger-DE'));
        self::assertEquals($emptyValue, $content->getFieldValue('field4', 'ger-DE'));
    }

    protected function assertFieldIds(Content $content1, Content $content2)
    {
        $fields1 = $this->mapFields($content1->getFields());
        $fields2 = $this->mapFields($content2->getFields());

        foreach ($fields1 as $fieldDefinitionIdentifier => $languageFieldIds) {
            foreach ($languageFieldIds as $languageCode => $fieldId) {
                self::assertEquals(
                    $fields2[$fieldDefinitionIdentifier][$languageCode],
                    $fieldId
                );
            }
        }
    }

    /**
     * @param \Ibexa\Contracts\Core\Repository\Values\Content\Field[] $fields
     *
     * @return array
     */
    protected function mapFields(array $fields)
    {
        $mappedFields = [];

        foreach ($fields as $field) {
            $mappedFields[$field->fieldDefIdentifier][$field->languageCode] = $field->id;
        }

        return $mappedFields;
    }
}
