<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Contracts\Core\Test\Repository\SetupFactory;

use Doctrine\DBAL\Connection;
use Ibexa\Bundle\Core\DependencyInjection\ServiceTags;
use Ibexa\Contracts\Core\Repository\Values\Filter\CriterionQueryBuilder as FilteringCriterionQueryBuilder;
use Ibexa\Contracts\Core\Repository\Values\Filter\SortClauseQueryBuilder as FilteringSortClauseQueryBuilder;
use Ibexa\Contracts\Core\Test\Persistence\Fixture;
use Ibexa\Contracts\Core\Test\Persistence\Fixture\FixtureImporter;
use Ibexa\Contracts\Core\Test\Persistence\Fixture\YamlFixture;
use Ibexa\Contracts\Core\Test\Repository\SetupFactory;
use Ibexa\Core\Base\Container\Compiler;
use Ibexa\Core\Base\ServiceContainer;
use Ibexa\Core\Persistence\Legacy\Content\Language\CachingHandler as CachingLanguageHandler;
use Ibexa\Core\Persistence\Legacy\Content\Type\MemoryCachingHandler as CachingContentTypeHandler;
use Ibexa\Core\Persistence\Legacy\Handler;
use Ibexa\Core\Repository\Values\User\UserReference;
use Ibexa\Tests\Core\Repository\IdManager;
use Ibexa\Tests\Core\Repository\LegacySchemaImporter;
use Ibexa\Tests\Integration\Core\LegacyTestContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Filesystem\Filesystem;

/**
 * A Test Factory is used to setup the infrastructure for a tests, based on a
 * specific repository implementation to test.
 */
class Legacy extends SetupFactory
{
    /**
     * Data source name.
     *
     * @var string
     */
    protected static $dsn;

    /**
     * Root dir for IO operations.
     *
     * @var string
     */
    protected static $ioRootDir;

    /**
     * Database type (sqlite, mysql, ...).
     *
     * @var string
     */
    protected static $db;

    /**
     * Service container.
     *
     * @var \Ibexa\Core\Base\ServiceContainer
     */
    protected static $serviceContainer;

    /**
     * If the DB schema has already been initialized.
     *
     * @var bool
     */
    protected static $schemaInitialized = false;

    /**
     * Cached in-memory initial database data fixture.
     *
     * @var \Ibexa\Contracts\Core\Test\Persistence\Fixture
     */
    private static $initialDataFixture;

    /**
     * Cached in-memory post insert SQL statements.
     *
     * @var string[]
     */
    private static $postInsertStatements;

    protected $repositoryReference = 'ibexa.api.repository';

    /** @var \Doctrine\DBAL\Connection */
    private $connection;

    /**
     * Creates a new setup factory.
     */
    public function __construct()
    {
        self::$dsn = getenv('DATABASE');
        if (!self::$dsn) {
            // use sqlite in-memory by default (does not need special handling for paratest as it's per process)
            self::$dsn = 'sqlite://:memory:';
        } elseif (getenv('TEST_TOKEN') !== false) {
            // Using paratest, assuming dsn ends with db name here...
            self::$dsn .= '_' . getenv('TEST_TOKEN');
        }

        if ($repositoryReference = getenv('REPOSITORY_SERVICE_ID')) {
            $this->repositoryReference = $repositoryReference;
        }

        self::$db = preg_replace('(^([a-z]+).*)', '\\1', self::$dsn);

        if (!isset(self::$ioRootDir)) {
            self::$ioRootDir = $this->createTemporaryDirectory();
        }
    }

    /**
     * Creates a temporary directory and returns its path name.
     *
     * @throw \RuntimeException If the root directory can't be created
     */
    private function createTemporaryDirectory(): string
    {
        $tmpFile = tempnam(
            sys_get_temp_dir(),
            'ibexa_core_io_tests_' . time()
        );
        unlink($tmpFile);

        $fs = new Filesystem();
        $fs->mkdir($tmpFile);

        $varDir = $tmpFile . '/var';
        if ($fs->exists($varDir)) {
            $fs->remove($varDir);
        }
        $fs->mkdir($varDir);

        return $tmpFile;
    }

    /**
     * Returns a configured repository for testing.
     *
     * @param bool $initializeFromScratch if the back end should be initialized
     *                                    from scratch or re-used
     *
     * @return \Ibexa\Contracts\Core\Repository\Repository
     */
    public function getRepository($initializeFromScratch = true)
    {
        if ($initializeFromScratch || !self::$schemaInitialized) {
            $this->initializeSchema();
            $this->insertData();
        }

        $this->clearInternalCaches();
        /** @var \Ibexa\Contracts\Core\Repository\Repository $repository */
        $repository = $this->getServiceContainer()->get($this->repositoryReference);

        // Set admin user as current user by default
        $repository->getPermissionResolver()->setCurrentUserReference(
            new UserReference(14)
        );

        return $repository;
    }

    /**
     * Returns a config value for $configKey.
     *
     * @param string $configKey
     *
     * @throws \Exception if $configKey could not be found.
     *
     * @return mixed
     */
    public function getConfigValue($configKey)
    {
        return $this->getServiceContainer()->getParameter($configKey);
    }

    /**
     * Returns a repository specific ID manager.
     *
     * @return \Ibexa\Tests\Integration\Core\Repository\IdManager
     */
    public function getIdManager()
    {
        return new IdManager\Php();
    }

    /**
     * Insert the database data.
     *
     * @throws \Doctrine\DBAL\Exception
     */
    public function insertData(): void
    {
        $connection = $this->getDatabaseConnection();
        $this->cleanupVarDir($this->getInitialVarDir());

        $fixtureImporter = new FixtureImporter($connection);
        $fixtureImporter->import($this->getInitialDataFixture());
    }

    protected function getInitialVarDir(): string
    {
        return __DIR__ . '/../../../../../var';
    }

    protected function cleanupVarDir($sourceDir)
    {
        $fs = new Filesystem();
        $varDir = self::$ioRootDir . '/var';
        if ($fs->exists($varDir)) {
            $fs->remove($varDir);
        }
        $fs->mkdir($varDir);
        $fs->mirror($sourceDir, $varDir);
    }

    /**
     * CLears internal in memory caches after inserting data circumventing the
     * API.
     */
    protected function clearInternalCaches()
    {
        /** @var $handler \Ibexa\Core\Persistence\Legacy\Handler */
        $handler = $this->getServiceContainer()->get(Handler::class);

        $contentLanguageHandler = $handler->contentLanguageHandler();
        if ($contentLanguageHandler instanceof CachingLanguageHandler) {
            $contentLanguageHandler->clearCache();
        }

        $contentTypeHandler = $handler->contentTypeHandler();
        if ($contentTypeHandler instanceof CachingContentTypeHandler) {
            $contentTypeHandler->clearCache();
        }

        /** @var $cachePool \Psr\Cache\CacheItemPoolInterface */
        $cachePool = $this->getServiceContainer()->get('ibexa.cache_pool');

        $cachePool->clear();
    }

    protected function getInitialDataFixture(): Fixture
    {
        if (!isset(self::$initialDataFixture)) {
            self::$initialDataFixture = new YamlFixture(
                dirname(__DIR__, 5) . '/tests/integration/Core/Repository/_fixtures/Legacy/data/test_data.yaml'
            );
        }

        return self::$initialDataFixture;
    }

    /**
     * Initializes the database schema.
     *
     * @throws \Doctrine\DBAL\ConnectionException
     */
    protected function initializeSchema(): void
    {
        if (!self::$schemaInitialized) {
            $schemaImporter = new LegacySchemaImporter($this->getDatabaseConnection());
            $schemaImporter->importSchema(
                dirname(__DIR__, 5) .
                '/src/bundle/Core/Resources/config/storage/legacy/schema.yaml'
            );

            self::$schemaInitialized = true;
        }
    }

    /**
     * Returns the raw database connection from the service container.
     *
     * @return \Doctrine\DBAL\Connection
     */
    private function getDatabaseConnection(): Connection
    {
        if (null === $this->connection) {
            $this->connection = $this->getServiceContainer()->get('ibexa.persistence.connection');
        }

        return $this->connection;
    }

    /**
     * Returns the service container used for initialization of the repository.
     *
     * @return \Ibexa\Core\Base\ServiceContainer
     */
    public function getServiceContainer()
    {
        if (!isset(self::$serviceContainer)) {
            $installDir = self::getInstallationDir();

            $containerBuilder = new LegacyTestContainerBuilder();
            $loader = $containerBuilder->getCoreLoader();
            /* @var \Symfony\Component\DependencyInjection\Loader\YamlFileLoader $loader */
            $loader->load('search_engines/legacy.yml');

            // tests/integration/Core/Resources/settings/integration_legacy.yml
            $loader->load('integration_legacy.yml');

            $this->externalBuildContainer($containerBuilder);

            $containerBuilder->setParameter(
                'ibexa.persistence.legacy.dsn',
                self::$dsn
            );

            $containerBuilder->setParameter(
                'ibexa.io.dir.root',
                self::$ioRootDir . '/' . $containerBuilder->getParameter('ibexa.io.dir.storage')
            );

            $containerBuilder->addCompilerPass(new Compiler\Search\FieldRegistryPass());

            $this->registerForAutoConfiguration($containerBuilder);

            // load overrides just before creating test Container
            // tests/integration/Core/Resources/settings/override.yml
            $loader->load('override.yml');

            self::$serviceContainer = new ServiceContainer(
                $containerBuilder,
                $installDir,
                $this->getCacheDir(),
                true,
                true
            );
        }

        return self::$serviceContainer;
    }

    /**
     * This is intended to be used from external repository in order to
     * enable container customization.
     *
     * @param \Symfony\Component\DependencyInjection\ContainerBuilder $containerBuilder
     */
    protected function externalBuildContainer(ContainerBuilder $containerBuilder)
    {
        // Does nothing by default
    }

    /**
     * Get the Database name.
     *
     * @return string
     */
    public function getDB()
    {
        return self::$db;
    }

    /**
     * Apply automatic configuration to needed Symfony Services.
     *
     * Note: Based on
     * {@see \Ibexa\Bundle\Core\DependencyInjection\IbexaCoreExtension::registerForAutoConfiguration},
     * but only for services needed by integration test setup.
     *
     * @see
     */
    private function registerForAutoConfiguration(ContainerBuilder $containerBuilder): void
    {
        $containerBuilder->registerForAutoconfiguration(FilteringCriterionQueryBuilder::class)
            ->addTag(ServiceTags::FILTERING_CRITERION_QUERY_BUILDER);

        $containerBuilder->registerForAutoconfiguration(FilteringSortClauseQueryBuilder::class)
            ->addTag(ServiceTags::FILTERING_SORT_CLAUSE_QUERY_BUILDER);
    }

    /**
     * @deprecated since Ibexa 4.0, rewrite test case to use {@see \Ibexa\Contracts\Core\Test\IbexaKernelTestCase} instead.
     */
    public static function getInstallationDir(): string
    {
        // package root directory:
        return realpath(__DIR__ . '/../../../../../');
    }

    /**
     * @deprecated since Ibexa 4.0, rewrite test case to use {@see \Ibexa\Contracts\Core\Test\IbexaKernelTestCase} instead.
     */
    public static function getCacheDir(): string
    {
        return self::getInstallationDir() . '/var/cache';
    }
}
