<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
namespace Ibexa\Tests\Core\Search\Legacy\Content;

use Ibexa\Contracts\Core\Persistence\Content\Type\Handler as SPIContentTypeHandler;
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
            self::$databaseInitialized = true;
        }
    }

    /**
     * Assert that the elements are.
     */
    protected function assertSearchResults($expectedIds, $searchResult)
    {
        $ids = $this->getIds($searchResult);
        $this->assertEquals($expectedIds, $ids);
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
     * @throws \Doctrine\DBAL\DBALException
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
                    $this->createMock(StorageDispatcherInterface::class)
                ),
                $this->createMock(ContentTypeUpdateHandler::class),
                $this->createMock(StorageDispatcherInterface::class)
            );
        }

        return $this->contentTypeHandler;
    }

    protected function getConverterRegistry()
    {
        if (!isset($this->converterRegistry)) {
            $this->converterRegistry = new ConverterRegistry(
                [
                    'ezdatetime' => new Converter\DateAndTimeConverter(),
                    'ezinteger' => new Converter\IntegerConverter(),
                    'ezstring' => new Converter\TextLineConverter(),
                    'ezfloat' => new Converter\FloatConverter(),
                    'ezurl' => new Converter\UrlConverter(),
                    'ezboolean' => new Converter\CheckboxConverter(),
                    'ezkeyword' => new Converter\KeywordConverter(),
                    'ezauthor' => new Converter\AuthorConverter(),
                    'ezimage' => new Converter\NullConverter(),
                    'ezmultioption' => new Converter\NullConverter(),
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
}

class_alias(AbstractTestCase::class, 'eZ\Publish\Core\Search\Legacy\Tests\Content\AbstractTestCase');
