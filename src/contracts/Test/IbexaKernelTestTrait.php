<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\Core\Test;

use Doctrine\DBAL\Connection;
use Ibexa\Contracts\Core\Persistence\TransactionHandler;
use Ibexa\Contracts\Core\Repository\ContentService;
use Ibexa\Contracts\Core\Repository\ContentTypeService;
use Ibexa\Contracts\Core\Repository\LanguageService;
use Ibexa\Contracts\Core\Repository\LocationService;
use Ibexa\Contracts\Core\Repository\ObjectStateService;
use Ibexa\Contracts\Core\Repository\PermissionResolver;
use Ibexa\Contracts\Core\Repository\RoleService;
use Ibexa\Contracts\Core\Repository\SearchService;
use Ibexa\Contracts\Core\Repository\SectionService;
use Ibexa\Contracts\Core\Repository\TrashService;
use Ibexa\Contracts\Core\Repository\URLAliasService;
use Ibexa\Contracts\Core\Repository\UserService;
use Ibexa\Contracts\Core\Test\Persistence\Fixture\FixtureImporter;
use Ibexa\Core\Repository\Values\User\UserReference;
use Ibexa\Tests\Core\Repository\LegacySchemaImporter;
use RuntimeException;

/**
 *  @experimental
 */
trait IbexaKernelTestTrait
{
    final protected static function loadSchema(): void
    {
        $schemaImporter = self::getContainer()->get(LegacySchemaImporter::class);
        foreach (static::getSchemaFiles() as $schemaFile) {
            $schemaImporter->importSchema($schemaFile);
        }
    }

    /**
     * @return iterable<string>
     */
    protected static function getSchemaFiles(): iterable
    {
        yield from self::$kernel->getSchemaFiles();
    }

    final protected static function loadFixtures(): void
    {
        $fixtureImporter = self::getContainer()->get(FixtureImporter::class);
        foreach (static::getFixtures() as $fixture) {
            $fixtureImporter->import($fixture);
        }

        self::backfillLanguageBitmaskColumns();

        static::postLoadFixtures();
    }

    protected static function postLoadFixtures(): void
    {
        // nothing to do by default
    }

    /**
     * Fixture YAML files predate "always_available" becoming a plain column and the
     * "ibexa_content_translation"/"ibexa_content_version_translation" join tables, and only set
     * "language_mask" - mirror what the real AddContentAlwaysAvailableColumnsMigration/
     * AddLanguageTranslationTablesMigration backfills do, so fixture rows behave consistently with
     * rows written through the gateway.
     *
     * "ibexa_content_translation"/"ibexa_content_version_translation" aren't part of any fixture
     * YAML, so FixtureImporter never truncates them - this runs on every loadFixtures() call, so it
     * must clear them itself before recomputing, or a second test would violate their primary key.
     */
    private static function backfillLanguageBitmaskColumns(): void
    {
        $connection = self::getDoctrineConnection();

        $connection->executeStatement('DELETE FROM ibexa_content_translation');
        $connection->executeStatement('DELETE FROM ibexa_content_version_translation');

        $connection->executeStatement(
            'UPDATE ibexa_content SET always_available = 1 WHERE (language_mask & 1) = 1'
        );
        $connection->executeStatement(
            'UPDATE ibexa_content_version SET always_available = 1 WHERE (language_mask & 1) = 1'
        );
        $connection->executeStatement(
            'INSERT INTO ibexa_content_translation (content_id, language_id)
             SELECT c.id, l.id FROM ibexa_content c
             JOIN ibexa_content_language l ON (c.language_mask & l.id) = l.id'
        );
        $connection->executeStatement(
            'INSERT INTO ibexa_content_version_translation (content_version_id, language_id)
             SELECT v.id, l.id FROM ibexa_content_version v
             JOIN ibexa_content_language l ON (v.language_mask & l.id) = l.id'
        );
        $connection->executeStatement(
            'UPDATE ibexa_search_object_word_link SET language_id = (language_mask & -2)'
        );
        $connection->executeStatement(
            'UPDATE ibexa_search_object_word_link
             SET is_main_and_always_available = 1 WHERE (language_mask & 1) = 1'
        );

        $connection->executeStatement('DELETE FROM ibexa_url_alias_ml_translation');
        $connection->executeStatement(
            'UPDATE ibexa_url_alias_ml SET is_always_available = 1 WHERE (lang_mask & 1) = 1'
        );
        $connection->executeStatement(
            'INSERT INTO ibexa_url_alias_ml_translation (parent, text_md5, language_id)
             SELECT u.parent, u.text_md5, l.id FROM ibexa_url_alias_ml u
             JOIN ibexa_content_language l ON (u.lang_mask & l.id) = l.id'
        );
    }

    /**
     * @return iterable<\Ibexa\Contracts\Core\Test\Persistence\Fixture>
     */
    protected static function getFixtures(): iterable
    {
        yield from self::$kernel->getFixtures();
    }

    /**
     * @template T of object
     *
     * @phpstan-param class-string<T> $className
     *
     * @return T
     */
    final protected static function getServiceByClassName(string $className, ?string $id = null): object
    {
        if (!self::$booted) {
            static::bootKernel();
        }

        $serviceId = self::getTestServiceId($id, $className);
        $service = self::getContainer()->get($serviceId);
        assert(is_object($service) && is_a($service, $className));

        return $service;
    }

    protected static function getTestServiceId(?string $id, string $className): string
    {
        $kernel = self::$kernel;
        if (!$kernel instanceof IbexaTestKernelInterface) {
            throw new RuntimeException(sprintf(
                'Expected %s to be an instance of %s.',
                get_class($kernel),
                IbexaTestKernelInterface::class,
            ));
        }

        return $kernel::getAliasServiceId($id ?? $className);
    }

    protected static function getDoctrineConnection(): Connection
    {
        return self::getServiceByClassName(Connection::class);
    }

    protected static function getContentTypeService(): ContentTypeService
    {
        return self::getServiceByClassName(ContentTypeService::class);
    }

    protected static function getContentService(): ContentService
    {
        return self::getServiceByClassName(ContentService::class);
    }

    protected static function getLocationService(): LocationService
    {
        return self::getServiceByClassName(LocationService::class);
    }

    protected static function getPermissionResolver(): PermissionResolver
    {
        return self::getServiceByClassName(PermissionResolver::class);
    }

    protected static function getRoleService(): RoleService
    {
        return self::getServiceByClassName(RoleService::class);
    }

    protected static function getSearchService(): SearchService
    {
        return self::getServiceByClassName(SearchService::class);
    }

    protected static function getTransactionHandler(): TransactionHandler
    {
        return self::getServiceByClassName(TransactionHandler::class);
    }

    protected static function getUserService(): UserService
    {
        return self::getServiceByClassName(UserService::class);
    }

    protected static function getObjectStateService(): ObjectStateService
    {
        return self::getServiceByClassName(ObjectStateService::class);
    }

    protected static function getLanguageService(): LanguageService
    {
        return self::getServiceByClassName(LanguageService::class);
    }

    protected static function getSectionService(): SectionService
    {
        return self::getServiceByClassName(SectionService::class);
    }

    protected static function getUrlAliasService(): URLAliasService
    {
        return self::getServiceByClassName(URLAliasService::class);
    }

    protected static function getTrashService(): TrashService
    {
        return self::getServiceByClassName(TrashService::class);
    }

    protected static function setAnonymousUser(): void
    {
        $anonymousUserId = 10;
        self::getPermissionResolver()->setCurrentUserReference(new UserReference($anonymousUserId));
    }

    protected static function setAdministratorUser(): void
    {
        $adminUserId = 14;
        self::getPermissionResolver()->setCurrentUserReference(new UserReference($adminUserId));
    }
}
