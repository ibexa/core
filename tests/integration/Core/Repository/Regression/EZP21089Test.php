<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Integration\Core\Repository\Regression;

use DateTime;
use Ibexa\Tests\Integration\Core\Repository\BaseTestCase;

/**
 * Test case for issue EZP-21089.
 *
 * Issue EZP-21089
 *
 *     Creating an article with public api throw warning on xmltext in regards to relations
 *
 *     Creating an article with the public api will throw the following warning
 *     Warning: array_flip(): Can only flip STRING and INTEGER values! in eZ/Publish/Core/Repository/RelationProcessor.php on line 108
 */
class EZP21089Test extends BaseTestCase
{
    /** @var \Ibexa\Core\Repository\Values\ContentType\ContentType */
    private $contentType;

    protected function setUp(): void
    {
        parent::setUp();

        $repository = $this->getRepository();

        $contentTypeService = $repository->getContentTypeService();
        $permissionResolver = $repository->getPermissionResolver();

        $creatorId = $permissionResolver->getCurrentUserReference()->getUserId();
        $creationDate = new DateTime();

        $typeCreateStruct = $contentTypeService->newContentTypeCreateStruct(
            'new-type'
        );
        $typeCreateStruct->names = [
            'eng-GB' => 'title',
        ];
        $typeCreateStruct->descriptions = [
            'eng-GB' => 'description',
        ];
        $typeCreateStruct->remoteId = 'new-remoteid';
        $typeCreateStruct->creatorId = $creatorId;
        $typeCreateStruct->creationDate = $creationDate;
        $typeCreateStruct->mainLanguageCode = 'eng-GB';
        $typeCreateStruct->nameSchema = '<title>';
        $typeCreateStruct->urlAliasSchema = '<title>';

        $titleFieldCreate = $contentTypeService->newFieldDefinitionCreateStruct(
            'title',
            'ibexa_string'
        );
        $titleFieldCreate->names = [
            'eng-GB' => 'title',
        ];
        $titleFieldCreate->descriptions = [
            'eng-GB' => 'title description',
        ];
        $titleFieldCreate->fieldGroup = 'blog-content';
        $titleFieldCreate->position = 1;
        $titleFieldCreate->isTranslatable = true;
        $titleFieldCreate->isRequired = true;
        $titleFieldCreate->isInfoCollector = false;
        $titleFieldCreate->isSearchable = true;
        $titleFieldCreate->defaultValue = 'New text line';
        $typeCreateStruct->addFieldDefinition($titleFieldCreate);

        $objectRelationFieldCreate = $contentTypeService->newFieldDefinitionCreateStruct(
            'body',
            'ibexa_object_relation'
        );
        $objectRelationFieldCreate->names = [
            'eng-GB' => 'object relation',
        ];
        $objectRelationFieldCreate->descriptions = [
            'eng-GB' => 'object relation description',
        ];
        $objectRelationFieldCreate->fieldGroup = 'blog-content';
        $objectRelationFieldCreate->position = 2;
        $objectRelationFieldCreate->isTranslatable = false;
        $objectRelationFieldCreate->isRequired = false;
        $objectRelationFieldCreate->isInfoCollector = false;
        $objectRelationFieldCreate->isSearchable = false;
        $objectRelationFieldCreate->defaultValue = '';
        $typeCreateStruct->addFieldDefinition($objectRelationFieldCreate);

        $groupCreate = $contentTypeService->newContentTypeGroupCreateStruct(
            'first-group'
        );
        $groupCreate->creatorId = $creatorId;
        $groupCreate->creationDate = $creationDate;

        $type = $contentTypeService->createContentType(
            $typeCreateStruct,
            [$contentTypeService->createContentTypeGroup($groupCreate)]
        );

        $contentTypeService->publishContentTypeDraft($type);

        $this->contentType = $contentTypeService->loadContentType($type->id);
    }

    public function testCreateContent()
    {
        $repository = $this->getRepository();

        $contentService = $repository->getContentService();

        $contentCreateStruct = $contentService->newContentCreateStruct(
            $this->contentType,
            'eng-GB'
        );
        $contentCreateStruct->setField('title', 'Test');
        $contentService->createContent(
            $contentCreateStruct,
            [$repository->getLocationService()->newLocationCreateStruct(2)]
        );
    }
}
