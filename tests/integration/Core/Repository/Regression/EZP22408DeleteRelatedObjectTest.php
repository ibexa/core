<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Integration\Core\Repository\Regression;

use Ibexa\Contracts\Core\Repository\Values\Content\Content;
use Ibexa\Contracts\Core\Repository\Values\Content\LocationCreateStruct;
use Ibexa\Contracts\Core\Repository\Values\ContentType\ContentType;
use Ibexa\Contracts\Core\Repository\Values\ContentType\FieldDefinitionCreateStruct;
use Ibexa\Core\FieldType\RelationList\Value;
use Ibexa\Tests\Integration\Core\Repository\BaseTestCase;

class EZP22408DeleteRelatedObjectTest extends BaseTestCase
{
    /** @var ContentType */
    private $testContentType;

    protected function setUp(): void
    {
        parent::setUp();

        $this->createTestContentType();
    }

    public function testRelationListIsUpdatedWhenRelatedObjectIsDeleted()
    {
        $targetObject1 = $this->createTargetObject('Relation list target object 1');
        $targetObject2 = $this->createTargetObject('Relation list target object 2');
        $referenceObject = $this->createReferenceObject(
            'Reference object',
            [
                $targetObject1->id,
                $targetObject2->id,
            ]
        );

        $contentService = $this->getRepository()->getContentService();
        $contentService->deleteContent($targetObject1->contentInfo);

        $reloadedReferenceObject = $contentService->loadContent($referenceObject->id);
        /** @var Value */
        $relationListValue = $reloadedReferenceObject->getFieldValue('relation_list');
        self::assertSame([$targetObject2->id], $relationListValue->destinationContentIds);
    }

    public function testSingleRelationIsUpdatedWhenRelatedObjectIsDeleted()
    {
        $targetObject = $this->createTargetObject('Single relation target object');
        $referenceObject = $this->createReferenceObject(
            'Reference object',
            [],
            $targetObject->id
        );

        $contentService = $this->getRepository()->getContentService();
        $contentService->deleteContent($targetObject->contentInfo);

        $reloadedReferenceObject = $contentService->loadContent($referenceObject->id);
        /** @var \Ibexa\Core\FieldType\Relation\Value */
        $relationValue = $reloadedReferenceObject->getFieldValue('single_relation');
        self::assertEmpty($relationValue->destinationContentId);
    }

    private function createTestContentType()
    {
        $languageCode = $this->getMainLanguageCode();
        $contentTypeService = $this->getRepository()->getContentTypeService();

        $createStruct = $contentTypeService->newContentTypeCreateStruct('test_content_type');
        $createStruct->mainLanguageCode = $languageCode;
        $createStruct->names = [$languageCode => 'Test content type'];
        $createStruct->nameSchema = '<name>';
        $createStruct->urlAliasSchema = '<name>';

        $createStruct->addFieldDefinition(
            new FieldDefinitionCreateStruct(
                [
                    'fieldTypeIdentifier' => 'ibexa_string',
                    'identifier' => 'name',
                    'names' => [$languageCode => 'Name'],
                    'position' => 1,
                ]
            )
        );

        $createStruct->addFieldDefinition(
            new FieldDefinitionCreateStruct(
                [
                    'fieldTypeIdentifier' => 'ibexa_object_relation_list',
                    'identifier' => 'relation_list',
                    'names' => [$languageCode => 'Relation List'],
                    'position' => 2,
                ]
            )
        );

        $createStruct->addFieldDefinition(
            new FieldDefinitionCreateStruct(
                [
                    'fieldTypeIdentifier' => 'ibexa_object_relation',
                    'identifier' => 'single_relation',
                    'names' => [$languageCode => 'Single Relation'],
                    'position' => 3,
                ]
            )
        );

        $contentGroup = $contentTypeService->loadContentTypeGroupByIdentifier('Content');
        $this->testContentType = $contentTypeService->createContentType($createStruct, [$contentGroup]);
        $contentTypeService->publishContentTypeDraft($this->testContentType);
    }

    private function getMainLanguageCode(): string
    {
        return $this->getRepository()->getContentLanguageService()->getDefaultLanguageCode();
    }

    /**
     * @param string $name
     *
     * @return Content
     */
    private function createTargetObject($name)
    {
        $contentService = $this->getRepository()->getContentService();
        $createStruct = $contentService->newContentCreateStruct(
            $this->testContentType,
            $this->getMainLanguageCode()
        );
        $createStruct->setField('name', $name);

        $object = $contentService->createContent(
            $createStruct,
            [
                $this->getLocationCreateStruct(),
            ]
        );

        return $contentService->publishVersion($object->versionInfo);
    }

    /**
     * @param string $name
     * @param array $relationListTarget Array of destination content ids
     * @param int $singleRelationTarget Content id
     *
     * @return Content
     */
    private function createReferenceObject(
        $name,
        array $relationListTarget = [],
        $singleRelationTarget = null
    ) {
        $contentService = $this->getRepository()->getContentService();
        $createStruct = $contentService->newContentCreateStruct(
            $this->testContentType,
            $this->getMainLanguageCode()
        );

        $createStruct->setField('name', $name);
        if (!empty($relationListTarget)) {
            $createStruct->setField('relation_list', $relationListTarget);
        }

        if ($singleRelationTarget) {
            $createStruct->setField('single_relation', $singleRelationTarget);
        }

        $object = $contentService->createContent(
            $createStruct,
            [
                $this->getLocationCreateStruct(),
            ]
        );

        return $contentService->publishVersion($object->versionInfo);
    }

    /**
     * @return LocationCreateStruct
     */
    private function getLocationCreateStruct()
    {
        return $this->getRepository()->getLocationService()->newLocationCreateStruct(2);
    }
}
