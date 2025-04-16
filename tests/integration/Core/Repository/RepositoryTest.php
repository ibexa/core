<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Integration\Core\Repository;

use Exception;
use Ibexa\Contracts\Core\Repository\ContentService;
use Ibexa\Contracts\Core\Repository\ContentTypeService;
use Ibexa\Contracts\Core\Repository\FieldTypeService;
use Ibexa\Contracts\Core\Repository\LanguageService;
use Ibexa\Contracts\Core\Repository\LocationService;
use Ibexa\Contracts\Core\Repository\NotificationService;
use Ibexa\Contracts\Core\Repository\ObjectStateService;
use Ibexa\Contracts\Core\Repository\PermissionResolver;
use Ibexa\Contracts\Core\Repository\Repository;
use Ibexa\Contracts\Core\Repository\RoleService;
use Ibexa\Contracts\Core\Repository\SearchService;
use Ibexa\Contracts\Core\Repository\SectionService;
use Ibexa\Contracts\Core\Repository\TrashService;
use Ibexa\Contracts\Core\Repository\URLAliasService;
use Ibexa\Contracts\Core\Repository\URLWildcardService;
use Ibexa\Contracts\Core\Repository\UserService;

/**
 * Test case for operations in the Repository using in memory storage.
 *
 * @covers \Ibexa\Contracts\Core\Repository\Repository
 *
 * @group integration
 */
class RepositoryTest extends BaseTest
{
    /**
     * Test for the getRepository() method.
     */
    public function testGetRepository(): void
    {
        self::assertInstanceOf(Repository::class, $this->getSetupFactory()->getRepository(true));
    }

    /**
     * Test for the getContentService() method.
     *
     * @group content
     * @group user
     */
    public function testGetContentService(): void
    {
        $repository = $this->getRepository();
        self::assertInstanceOf(
            ContentService::class,
            $repository->getContentService()
        );
    }

    /**
     * Test for the getContentLanguageService() method.
     *
     * @group language
     *
     * @covers \Ibexa\Contracts\Core\Repository\Repository::getContentLanguageService()
     */
    public function testGetContentLanguageService(): void
    {
        $repository = $this->getRepository();
        self::assertInstanceOf(
            LanguageService::class,
            $repository->getContentLanguageService()
        );
    }

    /**
     * Test for the getContentTypeService() method.
     *
     * @group content-type
     * @group field-type
     * @group user
     */
    public function testGetContentTypeService(): void
    {
        $repository = $this->getRepository();
        self::assertInstanceOf(
            ContentTypeService::class,
            $repository->getContentTypeService()
        );
    }

    /**
     * Test for the getLocationService() method.
     *
     * @group location
     */
    public function testGetLocationService(): void
    {
        $repository = $this->getRepository();
        self::assertInstanceOf(
            LocationService::class,
            $repository->getLocationService()
        );
    }

    /**
     * Test for the getSectionService() method.
     *
     * @group section
     */
    public function testGetSectionService(): void
    {
        $repository = $this->getRepository();
        self::assertInstanceOf(
            SectionService::class,
            $repository->getSectionService()
        );
    }

    /**
     * Test for the getUserService() method.
     *
     * @group user
     */
    public function testGetUserService(): void
    {
        $repository = $this->getRepository();
        self::assertInstanceOf(
            UserService::class,
            $repository->getUserService()
        );
    }

    /**
     * Test for the getNotificationService() method.
     *
     * @group user
     */
    public function testGetNotificationService(): void
    {
        $repository = $this->getRepository();
        self::assertInstanceOf(
            NotificationService::class,
            $repository->getNotificationService()
        );
    }

    /**
     * Test for the getTrashService() method.
     *
     * @group trash
     */
    public function testGetTrashService(): void
    {
        $repository = $this->getRepository();
        self::assertInstanceOf(
            TrashService::class,
            $repository->getTrashService()
        );
    }

    /**
     * Test for the getRoleService() method.
     *
     * @group role
     */
    public function testGetRoleService(): void
    {
        $repository = $this->getRepository();
        self::assertInstanceOf(
            RoleService::class,
            $repository->getRoleService()
        );
    }

    /**
     * Test for the getURLAliasService() method.
     *
     * @group url-alias
     */
    public function testGetURLAliasService(): void
    {
        $repository = $this->getRepository();
        self::assertInstanceOf(
            URLAliasService::class,
            $repository->getURLAliasService()
        );
    }

    /**
     * Test for the getUrlWildcardService() method.
     *
     * @group url-wildcard
     */
    public function testGetURLWildcardService(): void
    {
        $repository = $this->getRepository();
        self::assertInstanceOf(
            URLWildcardService::class,
            $repository->getURLWildcardService()
        );
    }

    /**
     * Test for the getObjectStateService().
     *
     * @group object-state
     */
    public function testGetObjectStateService(): void
    {
        $repository = $this->getRepository();
        self::assertInstanceOf(
            ObjectStateService::class,
            $repository->getObjectStateService()
        );
    }

    /**
     * Test for the getFieldTypeService().
     *
     * @group object-state
     */
    public function testGetFieldTypeService(): void
    {
        $repository = $this->getRepository();
        self::assertInstanceOf(
            FieldTypeService::class,
            $repository->getFieldTypeService()
        );
    }

    /**
     * Test for the getSearchService() method.
     *
     * @group search
     */
    public function testGetSearchService(): void
    {
        $repository = $this->getRepository();

        self::assertInstanceOf(
            SearchService::class,
            $repository->getSearchService()
        );
    }

    /**
     * Test for the getSearchService() method.
     *
     * @group permission
     */
    public function testGetPermissionResolver(): void
    {
        $repository = $this->getRepository();

        self::assertInstanceOf(
            PermissionResolver::class,
            $repository->getPermissionResolver()
        );
    }

    /**
     * Test for the commit() method.
     */
    public function testCommit(): void
    {
        $repository = $this->getRepository();

        try {
            $repository->beginTransaction();
            $repository->commit();
        } catch (Exception $e) {
            // Cleanup hanging transaction on error
            $repository->rollback();
            throw $e;
        }
    }

    /**
     * Test for the commit() method.
     */
    public function testCommitThrowsRuntimeException(): void
    {
        $this->expectException(\RuntimeException::class);

        $repository = $this->getRepository();
        $repository->commit();
    }

    /**
     * Test for the rollback() method.
     */
    public function testRollback(): void
    {
        $repository = $this->getRepository();
        $repository->beginTransaction();
        $repository->rollback();
    }

    /**
     * Test for the rollback() method.
     */
    public function testRollbackThrowsRuntimeException(): void
    {
        $this->expectException(\RuntimeException::class);

        $repository = $this->getRepository();
        $repository->rollback();
    }
}
