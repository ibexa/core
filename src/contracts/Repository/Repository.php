<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\Core\Repository;

/**
 * Repository interface.
 */
interface Repository
{
    /**
     * Allows API execution to be performed with full access, sand-boxed.
     *
     * The closure sandbox will do a "catch-all" on all exceptions and rethrow after
     * re-setting the sudo flag.
     *
     * Example use:
     *     $location = $repository->sudo(function (Repository $repo) use ($locationId) {
     *             return $repo->getLocationService()->loadLocation($locationId)
     *         }
     *     );
     *
     * @template T
     *
     * @phpstan-param callable(Repository): T $callback
     *
     * @param Repository|null $outerRepository Optional, mostly
     *        for internal use but allows to specify Repository to pass to closure.
     *
     * @return T
     */
    public function sudo(
        callable $callback,
        ?Repository $outerRepository = null
    );

    /**
     * Get Content Service.
     *
     * Get service object to perform operations on Content objects and it's aggregate members.
     *
     * @return ContentService
     */
    public function getContentService(): ContentService;

    /**
     * Get Content Language Service.
     *
     * Get service object to perform operations on Content language objects
     *
     * @return LanguageService
     */
    public function getContentLanguageService(): LanguageService;

    /**
     * Get content type Service.
     *
     * Get service object to perform operations on content type objects and it's aggregate members.
     * ( Group, Field & FieldCategory )
     *
     * @return ContentTypeService
     */
    public function getContentTypeService(): ContentTypeService;

    /**
     * Get Content Location Service.
     *
     * Get service object to perform operations on Location objects and subtrees
     *
     * @return LocationService
     */
    public function getLocationService(): LocationService;

    /**
     * Get Content Trash service.
     *
     * Trash service allows to perform operations related to location trash
     * (trash/untrash, load/list from trash...)
     *
     * @return TrashService
     */
    public function getTrashService(): TrashService;

    /**
     * Get Content Section Service.
     *
     * Get Section service that lets you manipulate section objects
     *
     * @return SectionService
     */
    public function getSectionService(): SectionService;

    /**
     * Get Search Service.
     *
     * Get search service that lets you find content objects
     *
     * @return SearchService
     */
    public function getSearchService(): SearchService;

    /**
     * Get User Service.
     *
     * Get service object to perform operations on Users and UserGroup
     *
     * @return UserService
     */
    public function getUserService(): UserService;

    /**
     * Get URLAliasService.
     *
     * @return URLAliasService
     */
    public function getURLAliasService(): URLAliasService;

    /**
     * Get URLWildcardService.
     *
     * @return URLWildcardService
     */
    public function getURLWildcardService(): URLWildcardService;

    /**
     * Get ObjectStateService.
     *
     * @return ObjectStateService
     */
    public function getObjectStateService(): ObjectStateService;

    /**
     * Get RoleService.
     *
     * @return RoleService
     */
    public function getRoleService(): RoleService;

    /**
     * Get FieldTypeService.
     *
     * @return FieldTypeService
     */
    public function getFieldTypeService(): FieldTypeService;

    /**
     * Get PermissionResolver.
     *
     * @return PermissionResolver
     */
    public function getPermissionResolver(): PermissionResolver;

    /**
     * Get URLService.
     *
     * @return URLService
     */
    public function getURLService(): URLService;

    /**
     * Get BookmarkService.
     *
     * @return BookmarkService
     */
    public function getBookmarkService(): BookmarkService;

    /**
     * Get NotificationService.
     *
     * @return NotificationService
     */
    public function getNotificationService(): NotificationService;

    /**
     * Get UserPreferenceService.
     *
     * @return UserPreferenceService
     */
    public function getUserPreferenceService(): UserPreferenceService;

    /**
     * Begin transaction.
     *
     * Begins an transaction, make sure you'll call commit or rollback when done,
     * otherwise work will be lost.
     */
    public function beginTransaction(): void;

    /**
     * Commit transaction.
     *
     * Commit transaction, or throw exceptions if no transactions has been started.
     *
     * @throws \RuntimeException If no transaction has been started
     */
    public function commit(): void;

    /**
     * Rollback transaction.
     *
     * Rollback transaction, or throw exceptions if no transactions has been started.
     *
     * @throws \RuntimeException If no transaction has been started
     */
    public function rollback(): void;
}
