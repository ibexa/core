<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Integration\Core\FieldType\FieldConstraintsStorage;

use Ibexa\Contracts\Core\Persistence\Content\FieldTypeConstraints;
use Ibexa\Contracts\Core\Repository\ContentTypeService;
use Ibexa\Contracts\Core\Repository\PermissionResolver;
use Ibexa\Contracts\Core\Repository\Values\ContentType\ContentType;
use Ibexa\Contracts\Core\Repository\Values\ContentType\ContentTypeCreateStruct;
use Ibexa\Tests\Integration\Core\FieldType\FieldConstraintsStorage\Stub\ExampleFieldConstraintsStorage;
use Ibexa\Tests\Integration\Core\FieldType\FieldConstraintsStorage\Stub\ExampleFieldType;
use Ibexa\Tests\Integration\Core\Repository\BaseTestCase;

final class FieldConstraintsStorageTest extends BaseTestCase
{
    private const EXAMPLE_FIELD_IDENTIFIER = 'example';

    private const EXAMPLE_FIELD_SETTINGS = [
        'format' => 'AAA-000-99',
    ];

    private const EXAMPLE_FIELD_SETTINGS_UPDATED = [
        'format' => 'aaa-999-00',
    ];

    private const EXAMPLE_VALIDATOR_CONFIGURATION = [
        'StringLengthValidator' => [
            'minStringLength' => 3,
            'maxStringLength' => 10,
        ],
    ];

    private const EXAMPLE_VALIDATOR_CONFIGURATION_UPDATED = [
        'StringLengthValidator' => [
            'minStringLength' => 5,
            'maxStringLength' => 16,
        ],
    ];

    public function testStorageDataIsCreatedOnContentTypeCreate(): ContentType
    {
        $repository = $this->getRepository();

        $contentTypeService = $repository->getContentTypeService();
        $permissionResolver = $repository->getPermissionResolver();

        $fieldDefCreateStruct = $contentTypeService->newFieldDefinitionCreateStruct(
            self::EXAMPLE_FIELD_IDENTIFIER,
            ExampleFieldType::FIELD_TYPE_IDENTIFIER
        );

        $fieldDefCreateStruct->names = ['eng-GB' => 'Example'];
        $fieldDefCreateStruct->descriptions = [
            'eng-GB' => 'Example field with external storage for field constraints',
        ];
        $fieldDefCreateStruct->fieldSettings = self::EXAMPLE_FIELD_SETTINGS;
        $fieldDefCreateStruct->validatorConfiguration = self::EXAMPLE_VALIDATOR_CONFIGURATION;

        $contentTypeCreateStruct = $this->createTypeCreateStruct($contentTypeService, $permissionResolver);
        $contentTypeCreateStruct->addFieldDefinition($fieldDefCreateStruct);

        $contentType = $contentTypeService->createContentType($contentTypeCreateStruct, [
            $contentTypeService->loadContentTypeGroupByIdentifier('Content'),
        ]);

        $contentTypeService->publishContentTypeDraft($contentType);

        $storage = $this->getExampleFieldConstraintsStorage();

        self::assertTrue($storage->isPublished($contentType->fieldDefinitions->get(self::EXAMPLE_FIELD_IDENTIFIER)->id));

        self::assertEquals(
            new FieldTypeConstraints(
                [
                    'fieldSettings' => self::EXAMPLE_FIELD_SETTINGS,
                    'validators' => self::EXAMPLE_VALIDATOR_CONFIGURATION,
                ]
            ),
            $storage->getFieldConstraintsDataIfAvailable(
                $contentType->getFieldDefinition(self::EXAMPLE_FIELD_IDENTIFIER)->id
            )
        );

        return $contentTypeService->loadContentTypeByIdentifier($contentType->identifier);
    }

    /**
     * @depends Ibexa\Tests\Integration\Core\FieldType\FieldConstraintsStorage\FieldConstraintsStorageTest::testStorageDataIsCreatedOnContentTypeCreate
     */
    public function testStorageDataIsUpdatedOnContentTypeUpdate(ContentType $contentType): ContentType
    {
        $repository = $this->getRepository(false);

        $contentTypeService = $repository->getContentTypeService();

        $updateStruct = $contentTypeService->newFieldDefinitionUpdateStruct();
        $updateStruct->fieldSettings = self::EXAMPLE_FIELD_SETTINGS_UPDATED;
        $updateStruct->validatorConfiguration = self::EXAMPLE_VALIDATOR_CONFIGURATION_UPDATED;

        $fieldDefinition = $contentType->getFieldDefinition(self::EXAMPLE_FIELD_IDENTIFIER);

        $contentTypeDraft = $contentTypeService->createContentTypeDraft($contentType);
        $contentTypeService->updateFieldDefinition($contentTypeDraft, $fieldDefinition, $updateStruct);
        $contentTypeService->publishContentTypeDraft($contentTypeDraft);

        $actualFieldTypeConstraints = $this
            ->getExampleFieldConstraintsStorage()
            ->getFieldConstraintsDataIfAvailable(
                $contentType->getFieldDefinition(self::EXAMPLE_FIELD_IDENTIFIER)->id
            );

        self::assertEquals(
            new FieldTypeConstraints(
                [
                    'fieldSettings' => self::EXAMPLE_FIELD_SETTINGS_UPDATED,
                    'validators' => self::EXAMPLE_VALIDATOR_CONFIGURATION_UPDATED,
                ]
            ),
            $actualFieldTypeConstraints
        );

        return $contentTypeService->loadContentTypeByIdentifier($contentType->identifier);
    }

    /**
     * @depends Ibexa\Tests\Integration\Core\FieldType\FieldConstraintsStorage\FieldConstraintsStorageTest::testStorageDataIsUpdatedOnContentTypeUpdate
     */
    public function testStorageDataIsDeletedOnContentTypeDelete(ContentType $contentType): void
    {
        $fieldDefinition = $contentType->getFieldDefinition(self::EXAMPLE_FIELD_IDENTIFIER);

        $storage = $this->getExampleFieldConstraintsStorage();
        self::assertTrue($storage->hasFieldConstraintsData($fieldDefinition->id));

        $contentTypeService = $this->getRepository(false)->getContentTypeService();
        $contentTypeService->deleteContentType($contentType);

        self::assertFalse($storage->hasFieldConstraintsData($fieldDefinition->id));
    }

    private function createTypeCreateStruct(
        ContentTypeService $contentTypeService,
        PermissionResolver $permissionResolver
    ): ContentTypeCreateStruct {
        $creatorId = $this->generateId('user', $permissionResolver->getCurrentUserReference()->getUserId());

        $typeCreateStruct = $contentTypeService->newContentTypeCreateStruct('field_constraints_storage_test');
        $typeCreateStruct->mainLanguageCode = 'eng-GB';
        $typeCreateStruct->names = ['eng-GB' => 'FieldConstraintsStorageTest'];
        $typeCreateStruct->creatorId = $creatorId;
        $typeCreateStruct->creationDate = $this->createDateTime();

        return $typeCreateStruct;
    }

    private function getExampleFieldConstraintsStorage(): ExampleFieldConstraintsStorage
    {
        /** @var ExampleFieldConstraintsStorage $storage */
        $storage = $this->getSetupFactory()->getServiceContainer()->get(ExampleFieldConstraintsStorage::class);

        return $storage;
    }
}
