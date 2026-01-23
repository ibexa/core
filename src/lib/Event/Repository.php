<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\Event;

use Ibexa\Contracts\Core\Repository\BookmarkService;
use Ibexa\Contracts\Core\Repository\BookmarkService as BookmarkServiceInterface;
use Ibexa\Contracts\Core\Repository\ContentService;
use Ibexa\Contracts\Core\Repository\ContentService as ContentServiceInterface;
use Ibexa\Contracts\Core\Repository\ContentTypeService;
use Ibexa\Contracts\Core\Repository\ContentTypeService as ContentTypeServiceInterface;
use Ibexa\Contracts\Core\Repository\FieldTypeService;
use Ibexa\Contracts\Core\Repository\FieldTypeService as FieldTypeServiceInterface;
use Ibexa\Contracts\Core\Repository\LanguageService;
use Ibexa\Contracts\Core\Repository\LanguageService as LanguageServiceInterface;
use Ibexa\Contracts\Core\Repository\LocationService;
use Ibexa\Contracts\Core\Repository\LocationService as LocationServiceInterface;
use Ibexa\Contracts\Core\Repository\NotificationService;
use Ibexa\Contracts\Core\Repository\NotificationService as NotificationServiceInterface;
use Ibexa\Contracts\Core\Repository\ObjectStateService;
use Ibexa\Contracts\Core\Repository\ObjectStateService as ObjectStateServiceInterface;
use Ibexa\Contracts\Core\Repository\PermissionResolver as PermissionResolverInterface;
use Ibexa\Contracts\Core\Repository\Repository as RepositoryInterface;
use Ibexa\Contracts\Core\Repository\RoleService;
use Ibexa\Contracts\Core\Repository\RoleService as RoleServiceInterface;
use Ibexa\Contracts\Core\Repository\SearchService;
use Ibexa\Contracts\Core\Repository\SearchService as SearchServiceInterface;
use Ibexa\Contracts\Core\Repository\SectionService;
use Ibexa\Contracts\Core\Repository\SectionService as SectionServiceInterface;
use Ibexa\Contracts\Core\Repository\TrashService;
use Ibexa\Contracts\Core\Repository\TrashService as TrashServiceInterface;
use Ibexa\Contracts\Core\Repository\URLAliasService;
use Ibexa\Contracts\Core\Repository\URLAliasService as URLAliasServiceInterface;
use Ibexa\Contracts\Core\Repository\URLService;
use Ibexa\Contracts\Core\Repository\URLService as URLServiceInterface;
use Ibexa\Contracts\Core\Repository\URLWildcardService;
use Ibexa\Contracts\Core\Repository\URLWildcardService as URLWildcardServiceInterface;
use Ibexa\Contracts\Core\Repository\UserPreferenceService;
use Ibexa\Contracts\Core\Repository\UserPreferenceService as UserPreferenceServiceInterface;
use Ibexa\Contracts\Core\Repository\UserService;
use Ibexa\Contracts\Core\Repository\UserService as UserServiceInterface;

final class Repository implements RepositoryInterface
{
    /** @var RepositoryInterface */
    private $repository;

    /** @var BookmarkService */
    private $bookmarkService;

    /** @var ContentService */
    private $contentService;

    /** @var ContentTypeService */
    private $contentTypeService;

    /** @var FieldTypeService */
    private $fieldTypeService;

    /** @var LanguageService */
    private $languageService;

    /** @var LocationService */
    private $locationService;

    /** @var NotificationService */
    private $notificationService;

    /** @var ObjectStateService */
    private $objectStateService;

    /** @var RoleService */
    private $roleService;

    /** @var SearchService */
    private $searchService;

    /** @var SectionService */
    private $sectionService;

    /** @var TrashService */
    private $trashService;

    /** @var URLAliasService */
    private $urlAliasService;

    /** @var URLService */
    private $urlService;

    /** @var URLWildcardService */
    private $urlWildcardService;

    /** @var UserPreferenceService */
    private $userPreferenceService;

    /** @var UserService */
    private $userService;

    public function __construct(
        RepositoryInterface $repository,
        BookmarkServiceInterface $bookmarkService,
        ContentServiceInterface $contentService,
        ContentTypeServiceInterface $contentTypeService,
        FieldTypeServiceInterface $fieldTypeService,
        LanguageServiceInterface $languageService,
        LocationServiceInterface $locationService,
        NotificationServiceInterface $notificationService,
        ObjectStateServiceInterface $objectStateService,
        RoleServiceInterface $roleService,
        SearchServiceInterface $searchService,
        SectionServiceInterface $sectionService,
        TrashServiceInterface $trashService,
        URLAliasServiceInterface $urlAliasService,
        URLServiceInterface $urlService,
        URLWildcardServiceInterface $urlWildcardService,
        UserPreferenceServiceInterface $userPreferenceService,
        UserServiceInterface $userService
    ) {
        $this->repository = $repository;
        $this->bookmarkService = $bookmarkService;
        $this->contentService = $contentService;
        $this->contentTypeService = $contentTypeService;
        $this->fieldTypeService = $fieldTypeService;
        $this->languageService = $languageService;
        $this->locationService = $locationService;
        $this->notificationService = $notificationService;
        $this->objectStateService = $objectStateService;
        $this->roleService = $roleService;
        $this->searchService = $searchService;
        $this->sectionService = $sectionService;
        $this->trashService = $trashService;
        $this->urlAliasService = $urlAliasService;
        $this->urlService = $urlService;
        $this->urlWildcardService = $urlWildcardService;
        $this->userPreferenceService = $userPreferenceService;
        $this->userService = $userService;
    }

    public function sudo(
        callable $callback,
        ?RepositoryInterface $outerRepository = null
    ) {
        return $this->repository->sudo($callback, $outerRepository);
    }

    public function beginTransaction(): void
    {
        $this->repository->beginTransaction();
    }

    public function commit(): void
    {
        $this->repository->commit();
    }

    public function rollback(): void
    {
        $this->repository->rollback();
    }

    public function getPermissionResolver(): PermissionResolverInterface
    {
        return $this->repository->getPermissionResolver();
    }

    public function getBookmarkService(): BookmarkServiceInterface
    {
        return $this->bookmarkService;
    }

    public function getContentService(): ContentServiceInterface
    {
        return $this->contentService;
    }

    public function getContentTypeService(): ContentTypeServiceInterface
    {
        return $this->contentTypeService;
    }

    public function getFieldTypeService(): FieldTypeServiceInterface
    {
        return $this->fieldTypeService;
    }

    public function getContentLanguageService(): LanguageServiceInterface
    {
        return $this->languageService;
    }

    public function getLocationService(): LocationServiceInterface
    {
        return $this->locationService;
    }

    public function getNotificationService(): NotificationServiceInterface
    {
        return $this->notificationService;
    }

    public function getObjectStateService(): ObjectStateServiceInterface
    {
        return $this->objectStateService;
    }

    public function getRoleService(): RoleServiceInterface
    {
        return $this->roleService;
    }

    public function getSearchService(): SearchServiceInterface
    {
        return $this->searchService;
    }

    public function getSectionService(): SectionServiceInterface
    {
        return $this->sectionService;
    }

    public function getTrashService(): TrashServiceInterface
    {
        return $this->trashService;
    }

    public function getURLAliasService(): URLAliasServiceInterface
    {
        return $this->urlAliasService;
    }

    public function getURLService(): URLServiceInterface
    {
        return $this->urlService;
    }

    public function getURLWildcardService(): URLWildcardServiceInterface
    {
        return $this->urlWildcardService;
    }

    public function getUserPreferenceService(): UserPreferenceServiceInterface
    {
        return $this->userPreferenceService;
    }

    public function getUserService(): UserServiceInterface
    {
        return $this->userService;
    }
}
