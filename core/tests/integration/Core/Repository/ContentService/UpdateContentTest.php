<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Integration\Core\Repository\ContentService;

use Ibexa\Contracts\Core\Repository\Values\Content\Content;
use Ibexa\Contracts\Core\Repository\Values\Content\ContentInfo;
use Ibexa\Contracts\Core\Repository\Values\Content\Section;
use Ibexa\Contracts\Core\Repository\Values\User\Limitation\SectionLimitation;
use Ibexa\Contracts\Core\Repository\Values\User\User;
use Ibexa\Tests\Integration\Core\RepositoryTestCase;

/**
 * @covers \Ibexa\Contracts\Core\Repository\ContentService
 */
final class UpdateContentTest extends RepositoryTestCase
{
    /**
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\Exception
     */
    public function testUpdateContentHavingPrivateRelation(): void
    {
        $sectionService = self::getSectionService();
        $contentService = self::getContentService();
        $permissionResolver = self::getPermissionResolver();

        $this->addRelationFieldToFolderContentType();

        $privateSection = $this->createPrivateSection();

        $folderPrivate = $this->createFolder(['eng-GB' => 'Private Folder'], 2);
        $sectionService->assignSection($folderPrivate->getContentInfo(), $privateSection);

        // Create folder with relation to 'Private Folder'
        $folder = $this->createFolderWithRelations([$folderPrivate->getId()]);

        $userWithRoleLimitation = $this->createUserWithNoAccessToPrivateSection();

        // Create & publish new $folder version as $editor
        $permissionResolver->setCurrentUserReference($userWithRoleLimitation);
        $folder = $this->publishVersionWithoutChanges($folder->getContentInfo());

        // Read relations & check if count($relations) is unchanged
        self::setAdministratorUser();
        $relations = $contentService->loadRelationList($folder->getVersionInfo());
        self::assertCount(1, $relations->items);
    }

    /**
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\Exception
     */
    private function addRelationFieldToFolderContentType(): void
    {
        $contentTypeService = self::getContentTypeService();
        $folderType = $contentTypeService->loadContentTypeByIdentifier('folder');
        $folderTypeDraft = $contentTypeService->createContentTypeDraft($folderType);

        $relationsFieldCreateStruct = $contentTypeService->newFieldDefinitionCreateStruct(
            'relations',
            'ibexa_object_relation_list'
        );
        $relationsFieldCreateStruct->names = ['eng-GB' => 'Relations'];
        $contentTypeService->addFieldDefinition($folderTypeDraft, $relationsFieldCreateStruct);
        $contentTypeService->publishContentTypeDraft($folderTypeDraft);
    }

    /**
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\UnauthorizedException
     */
    private function createPrivateSection(): Section
    {
        $sectionService = self::getSectionService();

        $sectionCreateStruct = $sectionService->newSectionCreateStruct();
        $sectionCreateStruct->identifier = 'private';
        $sectionCreateStruct->name = 'Private Section';

        return $sectionService->createSection($sectionCreateStruct);
    }

    /**
     * @param int[] $relationListTarget
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\Exception
     */
    private function createFolderWithRelations(array $relationListTarget): Content
    {
        $contentService = self::getContentService();

        $folder = $this->createFolder(['eng-GB' => 'Folder with private relation'], 2);
        $folderDraft = $contentService->createContentDraft($folder->getContentInfo());
        $folderUpdateStruct = $contentService->newContentUpdateStruct();
        $folderUpdateStruct->setField('relations', $relationListTarget);

        $folder = $contentService->updateContent($folderDraft->getVersionInfo(), $folderUpdateStruct);

        return $contentService->publishVersion($folder->getVersionInfo());
    }

    /**
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\UnauthorizedException
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\LimitationValidationException
     */
    private function assignToUserRoleWithStandardSectionLimitation(User $user): void
    {
        $sectionService = self::getSectionService();
        $roleService = self::getRoleService();

        $roleCreateStruct = $roleService->newRoleCreateStruct('limited_access');
        $roleCreateStruct->addPolicy($roleService->newPolicyCreateStruct('*', '*'));
        $role = $roleService->createRole($roleCreateStruct);
        $roleService->publishRoleDraft($role);

        // limit access to standard section only on the role assignment level
        $standardSection = $sectionService->loadSectionByIdentifier('standard');
        $roleService->assignRoleToUser(
            $role,
            $user,
            new SectionLimitation(['limitationValues' => [$standardSection->id]])
        );
    }

    /**
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\Exception
     */
    private function createUserWithNoAccessToPrivateSection(): User
    {
        $user = $this->createUser('test.editor', 'Editor', 'Test');
        $this->assignToUserRoleWithStandardSectionLimitation($user);

        return $user;
    }

    /**
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\Exception
     */
    private function publishVersionWithoutChanges(ContentInfo $contentInfo): Content
    {
        $contentService = self::getContentService();

        $folderDraft = $contentService->createContentDraft($contentInfo);
        $folderUpdateStruct = $contentService->newContentUpdateStruct();
        $folder = $contentService->updateContent($folderDraft->getVersionInfo(), $folderUpdateStruct);

        return $contentService->publishVersion($folder->getVersionInfo());
    }
}
