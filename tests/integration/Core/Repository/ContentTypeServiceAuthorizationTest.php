<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Integration\Core\Repository;

use Ibexa\Contracts\Core\Repository\ContentTypeService;
use Ibexa\Contracts\Core\Repository\Exceptions\UnauthorizedException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\DependsExternal;
use PHPUnit\Framework\Attributes\Group;

/**
 * Test case for operations in the ContentTypeServiceAuthorization using in memory storage.
 */
#[CoversClass(ContentTypeService::class)]
#[CoversMethod(ContentTypeService::class, 'createContentTypeGroup()')]
#[CoversMethod(ContentTypeService::class, 'updateContentTypeGroup()')]
#[CoversMethod(ContentTypeService::class, 'deleteContentTypeGroup()')]
#[CoversMethod(ContentTypeService::class, 'createContentType()')]
#[CoversMethod(ContentTypeService::class, 'updateContentTypeDraft()')]
#[CoversMethod(ContentTypeService::class, 'addFieldDefinition()')]
#[CoversMethod(ContentTypeService::class, 'removeFieldDefinition()')]
#[CoversMethod(ContentTypeService::class, 'updateFieldDefinition()')]
#[CoversMethod(ContentTypeService::class, 'publishContentTypeDraft()')]
#[CoversMethod(ContentTypeService::class, 'createContentTypeDraft()')]
#[CoversMethod(ContentTypeService::class, 'deleteContentType()')]
#[CoversMethod(ContentTypeService::class, 'copyContentType()')]
#[CoversMethod(ContentTypeService::class, 'assignContentTypeGroup()')]
#[CoversMethod(ContentTypeService::class, 'unassignContentTypeGroup()')]
#[Group('integration')]
#[Group('authorization')]
class ContentTypeServiceAuthorizationTest extends BaseContentTypeServiceTestCase
{
    /**
     * Test for the createContentTypeGroup() method.
     */
    #[DependsExternal(ContentTypeServiceTest::class, 'testCreateContentTypeGroup')]
    public function testCreateContentTypeGroupThrowsUnauthorizedException()
    {
        $this->expectException(UnauthorizedException::class);

        $repository = $this->getRepository();
        $permissionResolver = $repository->getPermissionResolver();

        $creatorId = $this->generateId('user', 14);
        $anonymousUserId = $this->generateId('user', 10);
        /* BEGIN: Use Case */
        // $anonymousUserId is the ID of the "Anonymous" user in a Ibexa
        // Publish demo installation.
        $userService = $repository->getUserService();
        $contentTypeService = $repository->getContentTypeService();

        $groupCreate = $contentTypeService->newContentTypeGroupCreateStruct(
            'new-group'
        );
        // $creatorId is the ID of the administrator user
        $groupCreate->creatorId = $creatorId;
        $groupCreate->creationDate = $this->createDateTime();
        /* @todo uncomment when support for multilingual names and descriptions is added EZP-24776
        $groupCreate->mainLanguageCode = 'ger-DE';
        $groupCreate->names = array( 'eng-GB' => 'A name.' );
        $groupCreate->descriptions = array( 'eng-GB' => 'A description.' );
        */

        // Set anonymous user
        $permissionResolver->setCurrentUserReference($userService->loadUser($anonymousUserId));

        // This call will fail with a "UnauthorizedException"
        $contentTypeService->createContentTypeGroup($groupCreate);
        /* END: Use Case */
    }

    /**
     * Test for the updateContentTypeGroup() method.
     */
    #[DependsExternal(ContentTypeServiceTest::class, 'testUpdateContentTypeGroup')]
    public function testUpdateContentTypeGroupThrowsUnauthorizedException()
    {
        $this->expectException(UnauthorizedException::class);

        $repository = $this->getRepository();
        $permissionResolver = $repository->getPermissionResolver();

        $modifierId = $this->generateId('user', 42);
        $anonymousUserId = $this->generateId('user', 10);
        /* BEGIN: Use Case */
        // $anonymousUserId is the ID of the "Anonymous" user in a Ibexa
        // Publish demo installation.
        $userService = $repository->getUserService();
        $contentTypeService = $repository->getContentTypeService();

        $group = $contentTypeService->loadContentTypeGroupByIdentifier('Setup');

        $groupUpdate = $contentTypeService->newContentTypeGroupUpdateStruct();

        $groupUpdate->identifier = 'Teardown';
        // $modifierId is the ID of a random user
        $groupUpdate->modifierId = $modifierId;
        $groupUpdate->modificationDate = $this->createDateTime();
        /* @todo uncomment when support for multilingual names and descriptions is added EZP-24776
        $groupUpdate->mainLanguageCode = 'eng-GB';

        $groupUpdate->names = array(
            'eng-GB' => 'A name',
            'eng-US' => 'A name',
        );
        $groupUpdate->descriptions = array(
            'eng-GB' => 'A description',
            'eng-US' => 'A description',
        );
        */

        // Set anonymous user
        $permissionResolver->setCurrentUserReference($userService->loadUser($anonymousUserId));

        // This call will fail with a "UnauthorizedException"
        $contentTypeService->updateContentTypeGroup($group, $groupUpdate);
        /* END: Use Case */
    }

    /**
     * Test for the deleteContentTypeGroup() method.
     */
    #[DependsExternal(ContentTypeServiceTest::class, 'testDeleteContentTypeGroup')]
    public function testDeleteContentTypeGroupThrowsUnauthorizedException()
    {
        $this->expectException(UnauthorizedException::class);

        $repository = $this->getRepository();
        $permissionResolver = $repository->getPermissionResolver();

        $anonymousUserId = $this->generateId('user', 10);
        /* BEGIN: Use Case */
        // $anonymousUserId is the ID of the "Anonymous" user in a Ibexa
        // Publish demo installation.
        $userService = $repository->getUserService();
        $contentTypeService = $repository->getContentTypeService();

        $groupCreate = $contentTypeService->newContentTypeGroupCreateStruct(
            'new-group'
        );
        $contentTypeService->createContentTypeGroup($groupCreate);

        // ...

        $group = $contentTypeService->loadContentTypeGroupByIdentifier('new-group');

        // Set anonymous user
        $permissionResolver->setCurrentUserReference($userService->loadUser($anonymousUserId));

        // This call will fail with a "UnauthorizedException"
        $contentTypeService->deleteContentTypeGroup($group);
        /* END: Use Case */
    }

    /**
     * Test for the createContentType() method.
     */
    #[DependsExternal(ContentTypeServiceTest::class, 'testCreateContentType')]
    public function testCreateContentTypeThrowsUnauthorizedException()
    {
        $this->expectException(UnauthorizedException::class);

        $repository = $this->getRepository();
        $permissionResolver = $repository->getPermissionResolver();

        $creatorId = $this->generateId('user', 14);
        $anonymousUserId = $this->generateId('user', 10);
        /* BEGIN: Use Case */
        // $anonymousUserId is the ID of the "Anonymous" user in a Ibexa
        // Publish demo installation.
        $userService = $repository->getUserService();
        $contentTypeService = $repository->getContentTypeService();

        $groupCreate = $contentTypeService->newContentTypeGroupCreateStruct(
            'new-group'
        );
        // $creatorId is the ID of the administrator user
        $groupCreate->creatorId = $creatorId;
        $groupCreate->creationDate = $this->createDateTime();
        $contentTypeGroup = $contentTypeService->createContentTypeGroup($groupCreate);

        $typeCreate = $contentTypeService->newContentTypeCreateStruct(
            'new-type'
        );
        // $creatorId is the ID of the administrator user
        $typeCreate->creatorId = $creatorId;
        $typeCreate->creationDate = $this->createDateTime();
        $typeCreate->mainLanguageCode = 'eng-GB';
        $typeCreate->names = ['eng-GB' => 'A name.'];
        $typeCreate->descriptions = ['eng-GB' => 'A description.'];

        $titleFieldCreateStruct = $contentTypeService->newFieldDefinitionCreateStruct('title', 'ibexa_string');
        $titleFieldCreateStruct->names = ['eng-GB' => 'Title'];
        $titleFieldCreateStruct->descriptions = ['eng-GB' => 'The Title'];
        $titleFieldCreateStruct->fieldGroup = 'content';
        $titleFieldCreateStruct->position = 10;
        $titleFieldCreateStruct->isTranslatable = true;
        $titleFieldCreateStruct->isRequired = true;
        $titleFieldCreateStruct->isSearchable = true;
        $typeCreate->addFieldDefinition($titleFieldCreateStruct);

        // Set anonymous user
        $permissionResolver->setCurrentUserReference($userService->loadUser($anonymousUserId));

        // This call will fail with a "UnauthorizedException"
        $contentTypeService->createContentType($typeCreate, [$contentTypeGroup]);
        /* END: Use Case */
    }

    /**
     * Test for the updateContentTypeDraft() method.
     */
    #[DependsExternal(ContentTypeServiceTest::class, 'testUpdateContentTypeDraft')]
    public function testUpdateContentTypeDraftThrowsUnauthorizedException()
    {
        $this->expectException(UnauthorizedException::class);

        $repository = $this->getRepository();
        $contentTypeService = $repository->getContentTypeService();
        $permissionResolver = $repository->getPermissionResolver();

        $modifierId = $this->generateId('user', 42);
        $anonymousUserId = $this->generateId('user', 10);
        /* BEGIN: Use Case */
        // $anonymousUserId is the ID of the "Anonymous" user in a Ibexa
        // Publish demo installation.
        $contentTypeDraft = $this->createContentTypeDraft();

        $typeUpdate = $contentTypeService->newContentTypeUpdateStruct();
        $typeUpdate->identifier = 'news-article';
        $typeUpdate->remoteId = '4cf35f5166fd31bf0cda859dc837e095daee9833';
        $typeUpdate->urlAliasSchema = 'url@alias|scheme';
        $typeUpdate->nameSchema = '@name@scheme@';
        $typeUpdate->isContainer = true;
        $typeUpdate->mainLanguageCode = 'ger-DE';
        $typeUpdate->defaultAlwaysAvailable = false;
        // $modifierId is the ID of a random user
        $typeUpdate->modifierId = $modifierId;
        $typeUpdate->modificationDate = $this->createDateTime();
        $typeUpdate->names = [
            'eng-GB' => 'News article',
            'ger-DE' => 'Nachrichten-Artikel',
        ];
        $typeUpdate->descriptions = [
            'eng-GB' => 'A news article',
            'ger-DE' => 'Ein Nachrichten-Artikel',
        ];

        // Load the user service
        $userService = $repository->getUserService();

        // Set anonymous user
        $permissionResolver->setCurrentUserReference($userService->loadUser($anonymousUserId));

        // This call will fail with a "UnauthorizedException"
        $contentTypeService->updateContentTypeDraft($contentTypeDraft, $typeUpdate);
        /* END: Use Case */
    }

    /**
     * Test for the addFieldDefinition() method.
     */
    #[DependsExternal(ContentTypeServiceTest::class, 'testAddFieldDefinition')]
    public function testAddFieldDefinitionThrowsUnauthorizedException()
    {
        $this->expectException(UnauthorizedException::class);

        $repository = $this->getRepository();
        $contentTypeService = $repository->getContentTypeService();
        $permissionResolver = $repository->getPermissionResolver();

        $anonymousUserId = $this->generateId('user', 10);
        /* BEGIN: Use Case */
        // $anonymousUserId is the ID of the "Anonymous" user in a Ibexa
        // Publish demo installation.
        $contentTypeDraft = $this->createContentTypeDraft();

        $fieldDefCreate = $contentTypeService->newFieldDefinitionCreateStruct('tags', 'string');
        $fieldDefCreate->names = [
            'eng-GB' => 'Tags',
            'ger-DE' => 'Schlagworte',
        ];
        $fieldDefCreate->descriptions = [
            'eng-GB' => 'Tags of the blog post',
            'ger-DE' => 'Schlagworte des Blog-Eintrages',
        ];
        $fieldDefCreate->fieldGroup = 'blog-meta';
        $fieldDefCreate->position = 1;
        $fieldDefCreate->isTranslatable = true;
        $fieldDefCreate->isRequired = true;
        $fieldDefCreate->isInfoCollector = false;
        $fieldDefCreate->validatorConfiguration = [
            'StringLengthValidator' => [
                'minStringLength' => 0,
                'maxStringLength' => 0,
            ],
        ];
        $fieldDefCreate->fieldSettings = [
            'textRows' => 10,
        ];
        $fieldDefCreate->isSearchable = true;

        // Load the user service
        $userService = $repository->getUserService();

        // Set anonymous user
        $permissionResolver->setCurrentUserReference($userService->loadUser($anonymousUserId));

        // This call will fail with a "UnauthorizedException"
        $contentTypeService->addFieldDefinition($contentTypeDraft, $fieldDefCreate);
        /* END: Use Case */
    }

    /**
     * Test for the removeFieldDefinition() method.
     */
    #[DependsExternal(ContentTypeServiceTest::class, 'testRemoveFieldDefinition')]
    public function testRemoveFieldDefinitionThrowsUnauthorizedException()
    {
        $this->expectException(UnauthorizedException::class);

        $repository = $this->getRepository();
        $contentTypeService = $repository->getContentTypeService();
        $permissionResolver = $repository->getPermissionResolver();

        $anonymousUserId = $this->generateId('user', 10);
        /* BEGIN: Use Case */
        // $anonymousUserId is the ID of the "Anonymous" user in a Ibexa
        // Publish demo installation.
        $contentTypeDraft = $this->createContentTypeDraft();

        // Load the user service
        $userService = $repository->getUserService();

        // Set anonymous user
        $permissionResolver->setCurrentUserReference($userService->loadUser($anonymousUserId));

        $bodyField = $contentTypeDraft->getFieldDefinition('body');

        // This call will fail with a "UnauthorizedException"
        $contentTypeService->removeFieldDefinition($contentTypeDraft, $bodyField);
        /* END: Use Case */
    }

    /**
     * Test for the updateFieldDefinition() method.
     */
    #[DependsExternal(ContentTypeServiceTest::class, 'testUpdateFieldDefinition')]
    public function testUpdateFieldDefinitionThrowsUnauthorizedException()
    {
        $this->expectException(UnauthorizedException::class);

        $repository = $this->getRepository();
        $contentTypeService = $repository->getContentTypeService();
        $permissionResolver = $repository->getPermissionResolver();

        $anonymousUserId = $this->generateId('user', 10);
        /* BEGIN: Use Case */
        // $anonymousUserId is the ID of the "Anonymous" user in a Ibexa
        // Publish demo installation.
        $contentTypeDraft = $this->createContentTypeDraft();

        // Load the user service
        $userService = $repository->getUserService();

        // Set anonymous user
        $permissionResolver->setCurrentUserReference($userService->loadUser($anonymousUserId));

        $bodyField = $contentTypeDraft->getFieldDefinition('body');

        $bodyUpdateStruct = $contentTypeService->newFieldDefinitionUpdateStruct();
        $bodyUpdateStruct->identifier = 'blog-body';
        $bodyUpdateStruct->names = [
            'eng-GB' => 'Blog post body',
            'ger-DE' => 'Blog-Eintrags-Textkörper',
        ];
        $bodyUpdateStruct->descriptions = [
            'eng-GB' => 'Blog post body of the blog post',
            'ger-DE' => 'Blog-Eintrags-Textkörper des Blog-Eintrages',
        ];
        $bodyUpdateStruct->fieldGroup = 'updated-blog-content';
        $bodyUpdateStruct->position = 3;
        $bodyUpdateStruct->isTranslatable = false;
        $bodyUpdateStruct->isRequired = false;
        $bodyUpdateStruct->isInfoCollector = true;
        $bodyUpdateStruct->validatorConfiguration = [];
        $bodyUpdateStruct->fieldSettings = [
            'textRows' => 60,
        ];
        $bodyUpdateStruct->isSearchable = false;

        // This call will fail with a "UnauthorizedException"
        $contentTypeService->updateFieldDefinition(
            $contentTypeDraft,
            $bodyField,
            $bodyUpdateStruct
        );
        /* END: Use Case */
    }

    /**
     * Test for the publishContentTypeDraft() method.
     */
    #[DependsExternal(ContentTypeServiceTest::class, 'testPublishContentTypeDraft')]
    public function testPublishContentTypeDraftThrowsUnauthorizedException()
    {
        $this->expectException(UnauthorizedException::class);

        $repository = $this->getRepository();
        $contentTypeService = $repository->getContentTypeService();
        $permissionResolver = $repository->getPermissionResolver();

        $anonymousUserId = $this->generateId('user', 10);
        /* BEGIN: Use Case */
        // $anonymousUserId is the ID of the "Anonymous" user in a Ibexa
        // Publish demo installation.
        $contentTypeDraft = $this->createContentTypeDraft();

        // Load the user service
        $userService = $repository->getUserService();

        // Set anonymous user
        $permissionResolver->setCurrentUserReference($userService->loadUser($anonymousUserId));

        // This call will fail with a "UnauthorizedException"
        $contentTypeService->publishContentTypeDraft($contentTypeDraft);
        /* END: Use Case */
    }

    /**
     * Test for the createContentTypeDraft() method.
     */
    #[DependsExternal(ContentTypeServiceTest::class, 'testCreateContentTypeDraft')]
    public function testCreateContentTypeDraftThrowsUnauthorizedException()
    {
        $this->expectException(UnauthorizedException::class);

        $repository = $this->getRepository();
        $permissionResolver = $repository->getPermissionResolver();

        $anonymousUserId = $this->generateId('user', 10);
        /* BEGIN: Use Case */
        // $anonymousUserId is the ID of the "Anonymous" user in a Ibexa
        // Publish demo installation.
        $contentTypeService = $repository->getContentTypeService();

        // Load the user service
        $userService = $repository->getUserService();

        // Set anonymous user
        $permissionResolver->setCurrentUserReference($userService->loadUser($anonymousUserId));

        $commentType = $contentTypeService->loadContentTypeByIdentifier('comment');

        // This call will fail with a "UnauthorizedException"
        $contentTypeService->createContentTypeDraft($commentType);
        /* END: Use Case */
    }

    /**
     * Test for the deleteContentType() method.
     */
    #[DependsExternal(ContentTypeServiceTest::class, 'testDeleteContentType')]
    public function testDeleteContentTypeThrowsUnauthorizedException()
    {
        $this->expectException(UnauthorizedException::class);

        $repository = $this->getRepository();
        $permissionResolver = $repository->getPermissionResolver();

        $anonymousUserId = $this->generateId('user', 10);
        /* BEGIN: Use Case */
        // $anonymousUserId is the ID of the "Anonymous" user in a Ibexa
        // Publish demo installation.
        $contentTypeService = $repository->getContentTypeService();

        // Load the user service
        $userService = $repository->getUserService();

        // Set anonymous user
        $permissionResolver->setCurrentUserReference($userService->loadUser($anonymousUserId));

        $commentType = $contentTypeService->loadContentTypeByIdentifier('comment');

        // This call will fail with a "UnauthorizedException"
        $contentTypeService->deleteContentType($commentType);
        /* END: Use Case */
    }

    /**
     * Test for the copyContentType() method.
     */
    #[DependsExternal(ContentTypeServiceTest::class, 'testCopyContentType')]
    public function testCopyContentTypeThrowsUnauthorizedException()
    {
        $this->expectException(UnauthorizedException::class);

        $repository = $this->getRepository();
        $permissionResolver = $repository->getPermissionResolver();

        $anonymousUserId = $this->generateId('user', 10);
        /* BEGIN: Use Case */
        // $anonymousUserId is the ID of the "Anonymous" user in a Ibexa
        // Publish demo installation.
        $contentTypeService = $repository->getContentTypeService();

        // Load the user service
        $userService = $repository->getUserService();

        // Set anonymous user
        $permissionResolver->setCurrentUserReference($userService->loadUser($anonymousUserId));

        $commentType = $contentTypeService->loadContentTypeByIdentifier('comment');

        // This call will fail with a "UnauthorizedException"
        $contentTypeService->copyContentType($commentType);
        /* END: Use Case */
    }

    /**
     * Test for the assignContentTypeGroup() method.
     */
    #[DependsExternal(ContentTypeServiceTest::class, 'testAssignContentTypeGroup')]
    public function testAssignContentTypeGroupThrowsUnauthorizedException()
    {
        $this->expectException(UnauthorizedException::class);

        $repository = $this->getRepository();
        $permissionResolver = $repository->getPermissionResolver();

        $anonymousUserId = $this->generateId('user', 10);
        /* BEGIN: Use Case */
        // $anonymousUserId is the ID of the "Anonymous" user in a Ibexa
        // Publish demo installation.
        $contentTypeService = $repository->getContentTypeService();

        // Load the user service
        $userService = $repository->getUserService();

        // Set anonymous user
        $permissionResolver->setCurrentUserReference($userService->loadUser($anonymousUserId));

        $mediaGroup = $contentTypeService->loadContentTypeGroupByIdentifier('Media');
        $folderType = $contentTypeService->loadContentTypeByIdentifier('folder');

        // This call will fail with a "UnauthorizedException"
        $contentTypeService->assignContentTypeGroup($folderType, $mediaGroup);
        /* END: Use Case */
    }

    /**
     * Test for the unassignContentTypeGroup() method.
     */
    #[DependsExternal(ContentTypeServiceTest::class, 'testUnassignContentTypeGroup')]
    public function testUnassignContentTypeGroupThrowsUnauthorizedException()
    {
        $this->expectException(UnauthorizedException::class);

        $repository = $this->getRepository();
        $permissionResolver = $repository->getPermissionResolver();

        $anonymousUserId = $this->generateId('user', 10);
        /* BEGIN: Use Case */
        // $anonymousUserId is the ID of the "Anonymous" user in a Ibexa
        // Publish demo installation.
        $contentTypeService = $repository->getContentTypeService();

        $folderType = $contentTypeService->loadContentTypeByIdentifier('folder');

        $mediaGroup = $contentTypeService->loadContentTypeGroupByIdentifier('Media');
        $contentGroup = $contentTypeService->loadContentTypeGroupByIdentifier('Content');

        // May not unassign last group
        $contentTypeService->assignContentTypeGroup($folderType, $mediaGroup);

        // Load the user service
        $userService = $repository->getUserService();

        // Set anonymous user
        $permissionResolver->setCurrentUserReference($userService->loadUser($anonymousUserId));

        // This call will fail with a "UnauthorizedException"
        $contentTypeService->unassignContentTypeGroup($folderType, $contentGroup);
        /* END: Use Case */
    }
}
