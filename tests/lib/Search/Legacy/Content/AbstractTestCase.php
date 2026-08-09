<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Core\Search\Legacy\Content;

use Ibexa\Contracts\Core\Persistence\Content\Type\Handler as SPIContentTypeHandler;
use Ibexa\Core\FieldType\FieldTypeAliasRegistry;
use Ibexa\Core\FieldType\FieldTypeAliasResolver;
use Ibexa\Core\FieldType\FieldTypeAliasResolverInterface;
use Ibexa\Core\Persistence\Legacy\Content\FieldValue\Converter;
use Ibexa\Core\Persistence\Legacy\Content\FieldValue\ConverterRegistry;
use Ibexa\Core\Persistence\Legacy\Content\Gateway;
use Ibexa\Core\Persistence\Legacy\Content\Mapper\ResolveVirtualFieldSubscriber;
use Ibexa\Core\Persistence\Legacy\Content\StorageRegistry;
use Ibexa\Core\Persistence\Legacy\Content\Type\Gateway\DoctrineDatabase as ContentTypeGateway;
use Ibexa\Core\Persistence\Legacy\Content\Type\Handler as ContentTypeHandler;
use Ibexa\Core\Persistence\Legacy\Content\Type\Mapper as ContentTypeMapper;
use Ibexa\Core\Persistence\Legacy\Content\Type\StorageDispatcherInterface;
use Ibexa\Core\Persistence\Legacy\Content\Type\Update\Handler as ContentTypeUpdateHandler;
use Ibexa\Tests\Core\Persistence\Legacy\Content\LanguageAwareTestCase;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Abstract test suite for legacy search.
 */
class AbstractTestCase extends LanguageAwareTestCase
{
    /** @var bool */
    private static $databaseInitialized = false;

    /**
     * Field registry mock.
     *
     * @var \Ibexa\Core\Persistence\Legacy\Content\FieldValue\ConverterRegistry
     */
    private $converterRegistry;

    /** @var \Ibexa\Contracts\Core\Persistence\Content\Type\Handler */
    private $contentTypeHandler;

    /**
     * Only set up once for these read only tests on a large fixture.
     *
     * Skipping the reset-up, since setting up for these tests takes quite some
     * time, which is not required to spent, since we are only reading from the
     * database anyways.
     */
    protected function setUp(): void
    {
        if (!self::$databaseInitialized) {
            parent::setUp();
            $this->insertDatabaseFixture(__DIR__ . '/../_fixtures/full_dump.php');
            $this->backfillAlwaysAvailableColumns();
            $this->backfillLanguageTranslationTables();
            $this->backfillSearchObjectWordLinkLanguageColumns();
            self::$databaseInitialized = true;
        }
    }

    /**
     * The "full_dump.php" fixture predates "ibexa_content_translation"/
     * "ibexa_content_version_translation" and only sets "language_mask" - mirror what the real
     * ibexa:languages:backfill-translations command does, so criterion/sort handlers that read the
     * new join tables (rather than decoding the mask) see the same translations the fixture's masks
     * encode.
     */
    private function backfillLanguageTranslationTables(): void
    {
        $connection = $this->getDatabaseConnection();
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
    }

    /**
     * The "full_dump.php" fixture predates the "always_available" columns on "ibexa_content" and
     * "ibexa_content_version" and only sets "language_mask" - mirror what the real
     * AddContentAlwaysAvailableColumnsMigration backfill does, so fixture rows behave consistently
     * with rows written through the gateway.
     */
    private function backfillAlwaysAvailableColumns(): void
    {
        $connection = $this->getDatabaseConnection();
        $connection->executeStatement(
            'UPDATE ibexa_content SET always_available = 1 WHERE (language_mask & 1) = 1'
        );
        $connection->executeStatement(
            'UPDATE ibexa_content_version SET always_available = 1 WHERE (language_mask & 1) = 1'
        );
    }

    /**
     * The "full_dump.php" fixture predates "ibexa_search_object_word_link"'s "language_id"/
     * "is_main_and_always_available" columns and only sets "language_mask" - mirror what the real
     * AddSearchObjectWordLinkLanguageIdColumnsMigration backfill does, so FullText criterion tests
     * see the same language membership the fixture's masks encode.
     */
    private function backfillSearchObjectWordLinkLanguageColumns(): void
    {
        $connection = $this->getDatabaseConnection();
        $connection->executeStatement(
            'UPDATE ibexa_search_object_word_link SET language_id = (language_mask & -2)'
        );
        $connection->executeStatement(
            'UPDATE ibexa_search_object_word_link SET is_main_and_always_available = 1 WHERE (language_mask & 1) = 1'
        );
    }

    /**
     * Assert that the elements are.
     */
    protected function assertSearchResults($expectedIds, $searchResult)
    {
        $ids = $this->getIds($searchResult);
        self::assertEquals($expectedIds, $ids);
    }

    protected function getIds($searchResult)
    {
        $ids = array_map(
            static function ($hit) {
                return $hit->valueObject->id;
            },
            $searchResult->searchHits
        );

        sort($ids);

        return $ids;
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     */
    protected function getContentTypeHandler(): SPIContentTypeHandler
    {
        if (!isset($this->contentTypeHandler)) {
            $this->contentTypeHandler = new ContentTypeHandler(
                new ContentTypeGateway(
                    $this->getDatabaseConnection(),
                    $this->getSharedGateway(),
                    $this->getLanguageMaskGenerator(),
                    $this->getCriterionVisitor()
                ),
                new ContentTypeMapper(
                    $this->getConverterRegistry(),
                    $this->getLanguageMaskGenerator(),
                    $this->createMock(StorageDispatcherInterface::class),
                    $this->getFieldTypeAliasResolver(),
                ),
                $this->createMock(ContentTypeUpdateHandler::class),
                $this->createMock(StorageDispatcherInterface::class),
                $this->getFieldTypeAliasResolver(),
            );
        }

        return $this->contentTypeHandler;
    }

    protected function getConverterRegistry()
    {
        if (!isset($this->converterRegistry)) {
            $this->converterRegistry = new ConverterRegistry(
                [
                    'ibexa_datetime' => new Converter\DateAndTimeConverter(),
                    'ibexa_integer' => new Converter\IntegerConverter(),
                    'ibexa_string' => new Converter\TextLineConverter(),
                    'ibexa_float' => new Converter\FloatConverter(),
                    'ibexa_url' => new Converter\UrlConverter(),
                    'ibexa_boolean' => new Converter\CheckboxConverter(),
                    'ibexa_keyword' => new Converter\KeywordConverter(),
                    'ibexa_author' => new Converter\AuthorConverter(),
                    'ibexa_image' => new Converter\NullConverter(),
                ]
            );
        }

        return $this->converterRegistry;
    }

    protected function getEventDispatcher(): EventDispatcherInterface
    {
        $eventDispatcher = new EventDispatcher();
        $eventDispatcher->addSubscriber(
            new ResolveVirtualFieldSubscriber(
                $this->getConverterRegistry(),
                $this->createMock(StorageRegistry::class),
                $this->createMock(Gateway::class)
            )
        );

        return $eventDispatcher;
    }

    protected function getFieldTypeAliasResolver(): FieldTypeAliasResolverInterface
    {
        $fieldTypeAliasRegistry = new FieldTypeAliasRegistry();

        return new FieldTypeAliasResolver($fieldTypeAliasRegistry);
    }
}
