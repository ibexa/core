<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Integration\Core\Repository\Values\User\Limitation;

use Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException;
use Ibexa\Contracts\Core\Repository\Exceptions\UnauthorizedException;
use Ibexa\Contracts\Core\Repository\ObjectStateService;
use Ibexa\Contracts\Core\Repository\Values\Content\ContentInfo;
use Ibexa\Contracts\Core\Repository\Values\Content\Query;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion;
use Ibexa\Contracts\Core\Repository\Values\ObjectState\ObjectState;
use Ibexa\Contracts\Core\Repository\Values\ObjectState\ObjectStateGroup;
use Ibexa\Contracts\Core\Repository\Values\User\Limitation\ObjectStateLimitation;
use Ibexa\Contracts\Core\Repository\Values\User\User;

/**
 * @covers \Ibexa\Contracts\Core\Repository\Values\User\Limitation\ObjectStateLimitation
 * @covers \Ibexa\Core\Limitation\ObjectStateLimitationType
 *
 * @group integration
 * @group limitation
 */
class ObjectStateLimitationTest extends BaseLimitationTestCase
{
    public const OBJECT_STATE_LOCK_GROUP_ID = 2;
    public const OBJECT_STATE_NOT_LOCKED_STATE_ID = 1;
    public const OBJECT_STATE_LOCKED_STATE_ID = 2;
    public const EDITOR_ROLE_IDENTIFIER = 'Editor';

    /**
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\ForbiddenException
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\UnauthorizedException
     */
    public function testObjectStateLimitationAllow(): void
    {
        $repository = $this->getRepository();
        $contentService = $repository->getContentService();

        $this->loginAsUser(
            $this->createUserWithObjectStateLimitation([self::OBJECT_STATE_NOT_LOCKED_STATE_ID])
        );

        $draft = $this->createWikiPageDraft();

        $contentService->deleteContent($draft->contentInfo);

        $this->expectException(NotFoundException::class);
        $contentService->loadContent($draft->id);
    }

    /**
     * @throws \ErrorException
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\ForbiddenException
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\UnauthorizedException
     */
    public function testObjectStateLimitationForbid(): void
    {
        $this->expectException(UnauthorizedException::class);

        $repository = $this->getRepository();
        $contentService = $repository->getContentService();

        $this->loginAsUser(
            $this->createUserWithObjectStateLimitation([self::OBJECT_STATE_LOCKED_STATE_ID])
        );

        $draft = $this->createWikiPageDraft();

        $this->expectException(UnauthorizedException::class);
        $contentService->deleteContent($draft->contentInfo);
    }

    /**
     * Checks if the action is correctly forbidden when using ObjectStateLimitation
     * with limitation values from two different StateGroups.
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\ForbiddenException
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\UnauthorizedException
     */
    public function testObjectStateLimitationForbidVariant(): void
    {
        $this->expectException(UnauthorizedException::class);
        $this->expectExceptionMessage('\'remove\' \'content\'');

        $repository = $this->getRepository();
        $objectStateGroup = $this->createObjectStateGroup();
        $objectState = $this->createObjectState($objectStateGroup);

        $contentService = $repository->getContentService();

        $this->loginAsUser(
            $this->createUserWithObjectStateLimitation(
                [
                    self::OBJECT_STATE_LOCKED_STATE_ID,
                    $objectState->id,
                ]
            )
        );

        $draft = $this->createWikiPageDraft();

        $this->expectException(UnauthorizedException::class);
        $this->expectExceptionMessage("'remove' 'content'");

        $contentService->deleteContent($draft->contentInfo);
    }

    /**
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\UnauthorizedException
     */
    private function createObjectStateGroup(): ObjectStateGroup
    {
        $objectStateService = $this->getRepository()->getObjectStateService();

        $objectStateGroupCreateStruct = $objectStateService->newObjectStateGroupCreateStruct('second_group');
        $objectStateGroupCreateStruct->defaultLanguageCode = 'eng-US';
        $objectStateGroupCreateStruct->names = ['eng-US' => 'Second Group'];

        return $objectStateService->createObjectStateGroup($objectStateGroupCreateStruct);
    }

    /**
     * Create new State and assign it to the $objectStateGroup.
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\UnauthorizedException
     */
    private function createObjectState(ObjectStateGroup $objectStateGroup): ObjectState
    {
        $objectStateService = $this->getRepository()->getObjectStateService();

        $objectStateCreateStruct = $objectStateService->newObjectStateCreateStruct('default_state');
        $objectStateCreateStruct->defaultLanguageCode = 'eng-US';
        $objectStateCreateStruct->names = ['eng-US' => 'Default state'];

        return $objectStateService->createObjectState($objectStateGroup, $objectStateCreateStruct);
    }

    /**
     * Checks if the search results are correctly filtered when using ObjectStateLimitation
     * with limitation values from two different StateGroups.
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\ForbiddenException
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\UnauthorizedException
     */
    public function testObjectStateLimitationSearch(): void
    {
        $repository = $this->getRepository();
        $permissionResolver = $repository->getPermissionResolver();

        $objectStateGroup = $this->createObjectStateGroup();
        $objectState = $this->createObjectState($objectStateGroup);

        $user = $this->createUserWithObjectStateLimitationOnContentRead(
            [
                self::OBJECT_STATE_NOT_LOCKED_STATE_ID,
                $objectState->id,
            ]
        );
        $adminUser = $permissionResolver->getCurrentUserReference();

        $wikiPage = $this->createWikiPage();

        $this->loginAsUser($user);

        $query = new Query();
        $query->filter = new Criterion\MatchAll();
        $query->limit = 50;

        $this->refreshSearch($repository);
        $searchResultsBefore = $repository->getSearchService()->findContent($query);

        $this->loginAsUser($adminUser);

        //change the Object State to the one that doesn't match the Limitation
        $stateService = $repository->getObjectStateService();
        $stateService->setContentState(
            $wikiPage->contentInfo,
            $stateService->loadObjectStateGroup(2),
            $stateService->loadObjectState(2)
        );

        $this->loginAsUser($user);

        $this->refreshSearch($repository);
        $searchResultsAfter = $repository->getSearchService()->findContent($query);

        self::assertEquals($searchResultsBefore->totalCount - 1, $searchResultsAfter->totalCount);
    }

    /**
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\ForbiddenException
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\UnauthorizedException
     */
    public function testUserWithNotLockedLimitationCanEditNotLockedContent(): void
    {
        $repository = $this->getRepository();
        $contentService = $repository->getContentService();
        $objectStateService = $repository->getObjectStateService();
        $lockGroup = $objectStateService->loadObjectStateGroup(self::OBJECT_STATE_LOCK_GROUP_ID);
        $notLockedState = $objectStateService->loadObjectState(self::OBJECT_STATE_NOT_LOCKED_STATE_ID);

        // sanity check
        self::assertSame('not_locked', $notLockedState->identifier);

        $this->loginAsUser(
            $this->createUserWithObjectStateLimitation([self::OBJECT_STATE_NOT_LOCKED_STATE_ID])
        );
        $draft = $this->createWikiPageDraft();

        $this->assertContentHasState(
            $objectStateService,
            $draft->contentInfo,
            $lockGroup,
            $notLockedState
        );

        $contentUpdate = $contentService->newContentUpdateStruct();
        $contentUpdate->setField('title', 'Updated test folder');
        $updatedDraft = $contentService->updateContent($draft->versionInfo, $contentUpdate);

        $this->assertContentHasState(
            $objectStateService,
            $updatedDraft->contentInfo,
            $lockGroup,
            $notLockedState
        );
    }

    /**
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\ForbiddenException
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\UnauthorizedException
     */
    protected function createUserWithObjectStateLimitation(array $objectStateIDs): User
    {
        return $this->createUserWithPolicies(
            uniqid('test', true),
            [
                ['module' => 'content', 'function' => 'read'],
                ['module' => 'content', 'function' => 'versionread'],
                ['module' => 'content', 'function' => 'create'],
                ['module' => 'content', 'function' => 'publish'],
                [
                    'module' => 'content',
                    'function' => 'edit',
                    'limitations' => [
                        new ObjectStateLimitation(['limitationValues' => $objectStateIDs]),
                    ],
                ],
                [
                    'module' => 'content',
                    'function' => 'remove',
                    'limitations' => [
                        new ObjectStateLimitation(['limitationValues' => $objectStateIDs]),
                    ],
                ],
            ]
        );
    }

    /**
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\ForbiddenException
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\UnauthorizedException
     */
    private function createUserWithObjectStateLimitationOnContentRead(array $values): User
    {
        return $this->createUserWithPolicies(
            uniqid('test', true),
            [
                [
                    'module' => 'content',
                    'function' => 'read',
                    'limitations' => [
                        new ObjectStateLimitation(
                            [
                                'limitationValues' => $values,
                            ]
                        ),
                    ],
                ],
            ]
        );
    }

    private function assertContentHasState(
        ObjectStateService $objectStateService,
        ContentInfo $contentInfo,
        ObjectStateGroup $lockGroup,
        ObjectState $objectState
    ): void {
        self::assertSame(
            $objectState->identifier,
            $objectStateService->getContentState($contentInfo, $lockGroup)->identifier
        );
    }
}
