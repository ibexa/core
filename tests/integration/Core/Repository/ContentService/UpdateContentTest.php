<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Integration\Core\Repository\ContentService;

use Ibexa\Contracts\Core\Repository\Values\User\Limitation\SectionLimitation;
use Ibexa\Tests\Integration\Core\Repository\BaseTest;

/**
 * @covers \Ibexa\Contracts\Core\Repository\ContentService
 */
final class UpdateContentTest extends BaseTest
{
    /**
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\UnauthorizedException
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\BadStateException
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\ContentTypeFieldDefinitionValidationException
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException
     */
    public function testUpdateContentHavingPrivateRelation(): void
    {
        $administratorUserId = $this->generateId('user', 14);
        $repository = $this->getRepository();
        $contentTypeService = $repository->getContentTypeService();
        $sectionService = $repository->getSectionService();
        $contentService = $repository->getContentService();
        $userService = $repository->getUserService();
        $permissionResolver = $repository->getPermissionResolver();

        /* BEGIN: Use Case */
        // 1. Add relation field to 'folder' ContentType
        $folderType = $contentTypeService->loadContentTypeByIdentifier('folder');
        $folderTypeDraft = $contentTypeService->createContentTypeDraft($folderType);

        $titleFieldCreateStruct = $contentTypeService->newFieldDefinitionCreateStruct('relations', 'ezobjectrelationlist');
        $titleFieldCreateStruct->names = ['eng-GB' => 'Relations'];
        $titleFieldCreateStruct->descriptions = ['eng-GB' => 'Relations'];
        $titleFieldCreateStruct->fieldGroup = 'content';
        $titleFieldCreateStruct->position = 10;
        $titleFieldCreateStruct->isTranslatable = false;
        $titleFieldCreateStruct->isRequired = false;
        $titleFieldCreateStruct->isSearchable = false;
        $contentTypeService->addFieldDefinition($folderTypeDraft, $titleFieldCreateStruct);
        $contentTypeService->publishContentTypeDraft($folderTypeDraft);

        // 2. Add Section 'private'
        $sectionCreateStruct = $sectionService->newSectionCreateStruct();
        $sectionCreateStruct->identifier = 'private';
        $sectionCreateStruct->name = 'Private Section';
        $privateSection = $sectionService->createSection($sectionCreateStruct);

        // 3. Add 'Private Folder'
        $folderPrivate = $this->createFolder(['eng-GB' => 'Private Folder'], 2);
        $sectionService->assignSection($folderPrivate->getContentInfo(), $privateSection);

        // 4. Add folder with relation to 'Private Folder'
        $folder = $this->createFolder(['eng-GB' => 'Folder with private relation'], 2);
        $folderDraft = $contentService->createContentDraft($folder->getContentInfo());
        $folderUpdateStruct = $contentService->newContentUpdateStruct();
        $relationListTarget = [$folderPrivate->id];
        $folderUpdateStruct->setField('relations', $relationListTarget);
        $folder = $contentService->updateContent($folderDraft->getVersionInfo(), $folderUpdateStruct);
        $contentService->publishVersion($folder->getVersionInfo());

        // 5. Create User that has no access to content in $privateSection
        $editorRole = $repository->getRoleService()->loadRole(3);
        // remove existing role assignments
        foreach ($repository->getRoleService()->getRoleAssignments($editorRole) as $role) {
            $repository->getRoleService()->removeRoleAssignment($role);
        }

        $editorUserGroup = $userService->loadUserGroup(13);
        // grant access to standard section
        $repository->getRoleService()->assignRoleToUserGroup(
            $editorRole,
            $editorUserGroup,
            new SectionLimitation(['limitationValues' => [1]])
        );
        $editor = $this->createUser('test.editor', 'Editor', 'Test');

        // 6. Create & publish new $folder version as $editor
        $permissionResolver->setCurrentUserReference($editor);
        $folderDraft = $contentService->createContentDraft($folder->getContentInfo());
        $folderUpdateStruct = $contentService->newContentUpdateStruct();
        $folder = $contentService->updateContent($folderDraft->getVersionInfo(), $folderUpdateStruct);
        $contentService->publishVersion($folder->getVersionInfo());

        // 7. Read relations & check if count($relations) is unchanged
        $permissionResolver->setCurrentUserReference($userService->loadUser($administratorUserId));
        $relations = $contentService->loadRelations($folder->getVersionInfo());
        self::assertEquals(1, count((array)$relations));
    }
}
