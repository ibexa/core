<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Integration\Core\Repository\Regression;

use Ibexa\Contracts\Core\Repository\Values\User\Limitation\SectionLimitation;
use Ibexa\Tests\Integration\Core\Repository\BaseTestCase;

/**
 * Test case for language issues in EZP-21798.
 *
 * Issue EZP-21798
 */
class EZP21798Test extends BaseTestCase
{
    /**
     * Test for EZP-21798 - Role changes not working correctly on Postgres 9.1.
     *
     * This test will verify that anonymous users can access to a new section
     * that it's allowed to
     */
    public function testRoleChanges()
    {
        $repository = $this->getRepository();
        $permissionResolver = $repository->getPermissionResolver();

        $sectionService = $repository->getSectionService();
        $contentService = $repository->getContentService();
        $contentTypeService = $repository->getContentTypeService();
        $locationService = $repository->getLocationService();
        $urlAliasService = $repository->getURLAliasService();
        $roleService = $repository->getRoleService();
        $userService = $repository->getUserService();

        $administratorUser = $userService->loadUser(14);
        $permissionResolver->setCurrentUserReference($administratorUser);

        // Create a new section
        $sectionCreateStruct = $sectionService->newSectionCreateStruct();
        $sectionCreateStruct->name = 'Private';
        $sectionCreateStruct->identifier = 'private';
        $sectionService->createSection($sectionCreateStruct);

        // Create a new folder
        $contentTypeFolder = $contentTypeService->loadContentTypeByIdentifier('folder');
        $contentCreateStructFolder = $contentService->newContentCreateStruct($contentTypeFolder, 'eng-GB');

        $contentCreateStructFolder->setField('name', 'News');

        $locationCreateStructFolder = $locationService->newLocationCreateStruct(2);
        $draftFolder = $contentService->createContent($contentCreateStructFolder, [$locationCreateStructFolder]);
        $contentFolder = $contentService->publishVersion($draftFolder->versionInfo);

        // Create a new article, inside the folder
        $contentTypeArticle = $contentTypeService->loadContentTypeByIdentifier('article');
        $contentCreateStructArticle = $contentService->newContentCreateStruct($contentTypeArticle, 'eng-GB');

        $contentCreateStructArticle->setField('title', 'Article 1');

        $newsLocation = $urlAliasService->lookup('/News');
        $locationNews = $locationService->loadLocation($newsLocation->destination);

        $locationCreateStructArticle = $locationService->newLocationCreateStruct($locationNews->id);
        $draftArticle = $contentService->createContent($contentCreateStructArticle, [$locationCreateStructArticle]);
        $contentArticle = $contentService->publishVersion($draftArticle->versionInfo);

        // Assign the article to the Private Section
        $section = $sectionService->loadSectionByIdentifier('private');
        $sectionService->assignSection($contentArticle->contentInfo, $section);

        $contentInfoarticle = $contentService->loadContentInfo($contentArticle->contentInfo->id);

        // Allow anonymous user to Content/Read/Section( Standard, Private )
        $role = $roleService->loadRoleByIdentifier('Anonymous');
        $roleDraft = $roleService->createRoleDraft($role);

        $numPolicies = count($roleDraft->getPolicies());
        /** @var \Ibexa\Contracts\Core\Repository\Values\User\PolicyDraft[] $policies */
        $policies = $roleDraft->getPolicies();
        $found = false;

        do {
            --$numPolicies;
            if ($policies[$numPolicies]->module == 'content' && $policies[$numPolicies]->function == 'read') {
                $found = true;
            }
        } while ($numPolicies > 0 && !$found);

        self::assertTrue($found, "Couldn't find policy with module 'content' and function 'read'");

        $newPolicy = $roleService->newPolicyUpdateStruct();
        $newLimitation = new SectionLimitation();
        $section = $sectionService->loadSectionByIdentifier('private');
        $newLimitation->limitationValues = [1, $section->id];
        $newPolicy->addLimitation($newLimitation);

        $roleService->updatePolicyByRoleDraft(
            $roleDraft,
            $policies[$numPolicies],
            $newPolicy
        );
        $roleService->publishRoleDraft($roleDraft);

        // Access /Folder/Article
        $anonymousUser = $userService->loadUser(10);
        $permissionResolver->setCurrentUserReference($anonymousUser);

        $contentService->loadContent($contentInfoarticle->id);
    }
}
