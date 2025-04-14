<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Core\Persistence\Legacy;

use Doctrine\Common\EventManager as DoctrineEventManager;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception as DBALException;
use Doctrine\DBAL\Query\QueryBuilder;
use Ibexa\Contracts\Core\Test\Persistence\Fixture\FileFixtureFactory;
use Ibexa\Contracts\Core\Test\Persistence\Fixture\FixtureImporter;
use Ibexa\Contracts\Core\Test\Persistence\Fixture\YamlFixture;
use Ibexa\Contracts\Core\Test\Repository\SetupFactory\Legacy;
use Ibexa\Core\Persistence\Legacy\SharedGateway;
use Ibexa\Core\Persistence\Legacy\SharedGateway\Gateway;
use Ibexa\Core\Search\Legacy\Content;
use Ibexa\Core\Search\Legacy\Content\Common\Gateway\CriteriaConverter;
use Ibexa\Core\Search\Legacy\Content\Common\Gateway\CriterionHandler;
use Ibexa\Core\Search\Legacy\Content\Common\Gateway\SortClauseConverter;
use Ibexa\DoctrineSchema\Database\DbPlatform\SqliteDbPlatform;
use Ibexa\Tests\Core\Persistence\DatabaseConnectionFactory;
use Ibexa\Tests\Core\Repository\LegacySchemaImporter;
use PHPUnit\Framework\TestCase as BaseTestCase;
use ReflectionObject;
use ReflectionProperty;

/**
 * Base test case for database related tests.
 */
abstract class TestCase extends BaseTestCase
{
    /**
     * DSN used for the DB backend.
     */
    protected string $dsn;

    /**
     * Doctrine Database connection -- to not be constructed twice for one test.
     *
     * @internal
     *
     * @var \Doctrine\DBAL\Connection
     */
    protected Connection $connection;

    private ?Gateway $sharedGateway = null;

    /**
     * Get data source name.
     *
     * The database connection string is read from an optional environment
     * variable "DATABASE_URL" and defaults to an in-memory SQLite database.
     */
    protected function getDsn(): string
    {
        if (!isset($this->dsn)) {
            $dsn = getenv('DATABASE_URL');
            $this->dsn = $dsn ?: 'sqlite://:memory:';
        }

        return $this->dsn;
    }

    /**
     * Get native Doctrine database connection.
     */
    final public function getDatabaseConnection(): Connection
    {
        if (!isset($this->connection)) {
            $eventManager = new DoctrineEventManager();
            $connectionFactory = new DatabaseConnectionFactory(
                [new SqliteDbPlatform()],
                $eventManager
            );

            try {
                $this->connection = $connectionFactory->createConnection($this->getDsn());
            } catch (DBALException $e) {
                self::fail('Connection failed: ' . $e->getMessage());
            }
        }

        return $this->connection;
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     */
    final public function getSharedGateway(): Gateway
    {
        if (!$this->sharedGateway) {
            $connection = $this->getDatabaseConnection();
            $factory = new SharedGateway\GatewayFactory(
                new SharedGateway\DatabasePlatform\FallbackGateway($connection),
                [
                    'sqlite' => new SharedGateway\DatabasePlatform\SqliteGateway($connection),
                ]
            );

            $this->sharedGateway = $factory->buildSharedGateway($connection);
        }

        return $this->sharedGateway;
    }

    /**
     * Resets the database on test setup, so we always operate on a clean database.
     */
    protected function setUp(): void
    {
        try {
            $schemaImporter = new LegacySchemaImporter($this->getDatabaseConnection());
            $schemaImporter->importSchema(
                dirname(__DIR__, 4) .
                '/src/bundle/Core/Resources/config/storage/legacy/schema.yaml'
            );
        } catch (DBALException $e) {
            self::fail(
                sprintf(
                    'Could not import legacy database schema: %s: %s',
                    get_class($e),
                    $e->getMessage()
                )
            );
        }
    }

    protected function tearDown(): void
    {
        unset($this->connection);
    }

    /**
     * @phpstan-param list<array<string,mixed>> $result
     */
    protected static function getResultTextRepresentation(array $result): string
    {
        return implode(
            "\n",
            array_map(
                static function ($row): string {
                    return implode(', ', $row);
                },
                $result
            )
        );
    }

    /**
     * Insert a database fixture from the given file.
     */
    protected function insertDatabaseFixture(string $file): void
    {
        try {
            $fixtureImporter = new FixtureImporter($this->getDatabaseConnection());
            $fixtureImporter->import((new FileFixtureFactory())->buildFixture($file));
        } catch (DBALException $e) {
            self::fail('Database fixture import failed: ' . $e->getMessage());
        }
    }

    /**
     * Insert test_data.yaml fixture, common for many test cases.
     *
     * See: eZ/Publish/API/Repository/Tests/_fixtures/Legacy/data/test_data.yaml
     */
    protected function insertSharedDatabaseFixture(): void
    {
        try {
            $fixtureImporter = new FixtureImporter($this->getDatabaseConnection());
            $fixtureImporter->import(
                new YamlFixture(
                    __DIR__ . '/../../../integration/Core/Repository/_fixtures/Legacy/data/test_data.yaml'
                )
            );
        } catch (DBALException $e) {
            self::fail('Database fixture import failed: ' . $e->getMessage());
        }
    }

    /**
     * Assert query result as correct.
     *
     * Builds text representations of the asserted and fetched query result,
     * based on a QueryBuilder object. Compares them using classic diff for
     * maximum readability of the differences between expectations and real
     * results.
     *
     * The expectation MUST be passed as a two-dimensional array containing
     * rows of columns.
     *
     * @phpstan-param list<array<string,mixed>> $expectation expected raw database rows
     *
     * @throws \Doctrine\DBAL\Exception
     */
    public static function assertQueryResult(
        array $expectation,
        QueryBuilder $query,
        string $message = ''
    ): void {
        $result = $query->executeQuery()->fetchAllAssociative();

        self::assertEquals(
            self::getResultTextRepresentation($expectation),
            self::getResultTextRepresentation($result),
            $message
        );
    }

    /**
     * Asserts correct property values on $object.
     *
     * Asserts that for all keys in $properties a corresponding property
     * exists in $object with the *same* value as in $properties.
     *
     * @param array<string, mixed> $properties
     * @param object $object
     */
    protected function assertPropertiesCorrect(array $properties, object $object): void
    {
        foreach ($properties as $propName => $propVal) {
            self::assertSame(
                $propVal,
                $object->$propName,
                "Incorrect value for \$$propName"
            );
        }
    }

    /**
     * Asserts $expStruct equals $actStruct in at least $propertyNames.
     *
     * Asserts that properties of $actStruct equal properties of $expStruct (not
     * vice versa!). If $propertyNames is null, all properties are checked.
     * Otherwise, $propertyNames provides a white list.
     *
     * @param string[]|null $propertyNames
     */
    protected function assertStructsEqual(
        object $expStruct,
        object $actStruct,
        ?array $propertyNames = null
    ): void {
        if ($propertyNames === null) {
            $propertyNames = $this->getPublicPropertyNames($expStruct);
        }
        foreach ($propertyNames as $propName) {
            self::assertEquals(
                $expStruct->$propName,
                $actStruct->$propName,
                "Properties \$$propName not same"
            );
        }
    }

    /**
     * Returns public property names in $object.
     *
     * @return string[]
     */
    protected function getPublicPropertyNames(object $object): array
    {
        $reflectionObject = new ReflectionObject($object);

        return array_map(
            static function ($prop): string {
                return $prop->getName();
            },
            $reflectionObject->getProperties(ReflectionProperty::IS_PUBLIC)
        );
    }

    /**
     * @deprecated since Ibexa 4.0, rewrite test case to use {@see \Ibexa\Contracts\Core\Test\IbexaKernelTestCase} instead.
     */
    protected static function getInstallationDir(): string
    {
        return Legacy::getInstallationDir();
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     */
    protected function getTrashCriteriaConverterDependency(): CriteriaConverter
    {
        $connection = $this->getDatabaseConnection();

        return new CriteriaConverter(
            [
                new CriterionHandler\LogicalAnd($connection),
                new CriterionHandler\SectionId($connection),
                new CriterionHandler\ContentTypeId($connection),
                new CriterionHandler\DateMetadata($connection),
                new CriterionHandler\UserMetadata($connection),
                new CriterionHandler\ContentName($connection),
            ]
        );
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     */
    protected function getTrashSortClauseConverterDependency(): SortClauseConverter
    {
        $connection = $this->getDatabaseConnection();

        return new SortClauseConverter(
            [
                new Content\Common\Gateway\SortClauseHandler\SectionName($connection),
                new Content\Common\Gateway\SortClauseHandler\ContentName($connection),
                new Content\Common\Gateway\SortClauseHandler\Trash\ContentTypeName($connection),
                new Content\Common\Gateway\SortClauseHandler\Trash\UserLogin($connection),
                new Content\Common\Gateway\SortClauseHandler\Trash\DateTrashed($connection),
                new Content\Location\Gateway\SortClauseHandler\Location\Path($connection),
                new Content\Location\Gateway\SortClauseHandler\Location\Depth($connection),
                new Content\Location\Gateway\SortClauseHandler\Location\Priority($connection),
            ]
        );
    }
}
