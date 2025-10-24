<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Integration\Core\Repository\Regression;

use Ibexa\Contracts\Core\Repository\Values\ContentType\ContentType;
use Ibexa\Core\FieldType\Integer\Value;
use Ibexa\Core\Persistence\Legacy\Exception\TypeNotFound as TypeNotFoundException;
use Ibexa\Tests\Integration\Core\Repository\BaseTestCase;

/**
 * Regression tests for the issue EZP-21109.
 */
class EZP21109IbexaIntegerTest extends BaseTestCase
{
    /**
     * The short name of the current class.
     *
     * @var string
     */
    protected $classShortName;

    /** @var ContentType */
    protected $contentType;

    protected function setUp(): void
    {
        parent::setUp();

        $reflect = new \ReflectionClass($this);
        $this->classShortName = $reflect->getShortName();

        $this->contentType = $this->createTestContentType();
    }

    protected function tearDown(): void
    {
        $this->deleteTestContentType();
        parent::tearDown();
    }

    /**
     * Assert that it is possible to store any integer value in an integer field with default settings.
     *
     * @dataProvider validIntegerValues
     */
    public function testIbexaIntegerWithDefaultValues(int $integerValue): void
    {
        $repository = $this->getRepository();
        $contentService = $repository->getContentService();
        $locationService = $repository->getLocationService();

        $contentCreateStruct = $contentService->newContentCreateStruct($this->contentType, 'eng-GB');
        $contentCreateStruct->setField('test', $integerValue);

        $location = $locationService->newLocationCreateStruct(2);

        $draft = $contentService->createContent($contentCreateStruct, [$location]);

        $contentService->publishVersion($draft->versionInfo);

        $content = $contentService->loadContent($draft->versionInfo->contentInfo->id);

        /** @var Value $fieldValue */
        $fieldValue = $content->getFieldValue('test');

        self::assertInstanceOf(Value::class, $fieldValue);

        self::assertEquals($integerValue, $fieldValue->value);

        $contentService->deleteContent($content->versionInfo->contentInfo);
    }

    public function validIntegerValues()
    {
        return [
            [0],
            [1],
            [-1],
            [2147483647],
            [-2147483647],
        ];
    }

    /**
     * Creates a Test ContentType for this test holding an ibexa_integerfield.
     *
     * @return ContentType
     */
    protected function createTestContentType()
    {
        $repository = $this->getRepository();
        $contentTypeService = $repository->getContentTypeService();
        $permissionResolver = $repository->getPermissionResolver();

        // Create a test class with an integer field type
        $typeGroup = $contentTypeService->loadContentTypeGroupByIdentifier('Content');

        $contentType = $contentTypeService->newContentTypeCreateStruct($this->classShortName);
        $contentType->creatorId = $permissionResolver->getCurrentUserReference()->getUserId();
        $contentType->mainLanguageCode = 'eng-GB';
        $contentType->names = [
            'eng-GB' => $this->classShortName,
        ];
        $contentType->nameSchema = '<test>';
        $contentType->urlAliasSchema = '<test>';
        $contentType->isContainer = false;
        $contentType->defaultAlwaysAvailable = true;

        // Field: IntegerTest
        $field = $contentTypeService->newFieldDefinitionCreateStruct('test', 'ibexa_integer');
        $field->names = [
            'eng-GB' => 'Test',
        ];
        $field->position = 10;
        $contentType->addFieldDefinition($field);

        $draft = $contentTypeService->createContentType($contentType, [$typeGroup]);

        $contentTypeService->publishContentTypeDraft($draft);

        return $contentTypeService->loadContentTypeByIdentifier($this->classShortName);
    }

    /**
     * Deletes the Test ContentType for this test.
     */
    protected function deleteTestContentType(): void
    {
        $repository = $this->getRepository();
        $contentTypeService = $repository->getContentTypeService();

        try {
            $contentType = $contentTypeService->loadContentTypeByIdentifier($this->classShortName);
            $contentTypeService->deleteContentType($contentType);
        } catch (TypeNotFoundException $e) {
            // This shouldn't throw an error
        }
    }
}
