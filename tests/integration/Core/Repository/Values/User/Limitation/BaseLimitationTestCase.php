<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Integration\Core\Repository\Values\User\Limitation;

use Ibexa\Contracts\Core\Repository\Values\Content\Content;
use Ibexa\Contracts\Core\Repository\Values\Content\ContentCreateStruct;
use Ibexa\Contracts\Core\Repository\Values\Content\Location;
use Ibexa\Contracts\Core\Repository\Values\Content\LocationCreateStruct;
use Ibexa\Contracts\Core\Repository\Values\User\PolicyCreateStruct;
use Ibexa\Contracts\Core\Repository\Values\User\Role;
use Ibexa\Tests\Integration\Core\Repository\BaseTestCase;

/**
 * Abstract base class for limitation tests.
 *
 * @group integration
 * @group limitation
 */
abstract class BaseLimitationTestCase extends BaseTestCase
{
    /**
     * Creates a published wiki page.
     */
    protected function createWikiPage(): Content
    {
        $repository = $this->getRepository();

        $contentService = $repository->getContentService();
        /* BEGIN: Inline */
        $draft = $this->createWikiPageDraft();

        $content = $contentService->publishVersion($draft->versionInfo);
        /* END: Inline */

        return $content;
    }

    /**
     * Creates a fresh clean content draft.
     */
    protected function createWikiPageDraft(): Content
    {
        $repository = $this->getRepository();
        $contentService = $repository->getContentService();

        // $parentLocationId is the id of the /Home/Contact-Us node
        $parentLocationId = $this->generateId('location', 60);

        $locationCreate = $this->createWikiPageLocationCreateStruct($parentLocationId);
        $wikiPageCreate = $this->createWikiPageContentCreateStruct();

        // Create a draft
        $draft = $contentService->createContent(
            $wikiPageCreate,
            [$locationCreate]
        );

        return $draft;
    }

    /**
     * Creates a basic LocationCreateStruct.
     */
    protected function createWikiPageLocationCreateStruct(int $parentLocationId): LocationCreateStruct
    {
        $repository = $this->getRepository();
        $locationService = $repository->getLocationService();

        $locationCreate = $locationService->newLocationCreateStruct($parentLocationId);

        $locationCreate->priority = 23;
        $locationCreate->hidden = true;
        $locationCreate->remoteId = '0123456789abcdef0123456789abcdef';
        $locationCreate->sortField = Location::SORT_FIELD_NODE_ID;
        $locationCreate->sortOrder = Location::SORT_ORDER_DESC;

        return $locationCreate;
    }

    /**
     * Creates a basic ContentCreateStruct.
     */
    protected function createWikiPageContentCreateStruct(
        ?int $ownerId = null,
        ?string $remoteId = 'abcdef0123456789abcdef0123456789'
    ): ContentCreateStruct {
        $repository = $this->getRepository();
        $contentTypeService = $repository->getContentTypeService();
        $contentService = $repository->getContentService();

        $sectionId = $this->generateId('section', 1);

        // Load content type
        $wikiPageType = $contentTypeService->loadContentTypeByIdentifier('wiki_page');

        // Configure new content object
        $wikiPageCreate = $contentService->newContentCreateStruct($wikiPageType, 'eng-US');
        $wikiPageCreate->setField('title', 'An awesome wiki page');

        if (null === $remoteId) {
            $remoteId = md5(time());
        }

        $wikiPageCreate->remoteId = $remoteId;

        // $sectionId is the ID of section 1
        $wikiPageCreate->sectionId = $sectionId;
        $wikiPageCreate->alwaysAvailable = true;

        // Optional: Configure owner
        if ($ownerId !== null) {
            $wikiPageCreate->ownerId = $ownerId;
        }

        return $wikiPageCreate;
    }

    protected function addPolicyToRole(string $roleIdentifier, PolicyCreateStruct $policyCreateStruct): Role
    {
        $roleService = $this->getRepository()->getRoleService();

        $role = $roleService->loadRoleByIdentifier($roleIdentifier);
        $roleDraft = $roleService->createRoleDraft($role);
        $roleService->addPolicyByRoleDraft($roleDraft, $policyCreateStruct);
        $roleService->publishRoleDraft($roleDraft);

        return $roleService->loadRoleByIdentifier($roleIdentifier);
    }
}
