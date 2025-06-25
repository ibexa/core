<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\Core\Test\Repository\SetupFactory;

use Doctrine\DBAL\Connection;
use Ibexa\Bundle\Core\DependencyInjection\ServiceTags;
use Ibexa\Contracts\Core\Repository\Repository;
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
use Ibexa\Tests\Core\Repository\IdManager\Php;
use Ibexa\Tests\Core\Repository\LegacySchemaImporter;
use Ibexa\Tests\Integration\Core\LegacyTestContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Filesystem\Filesystem;

/**
 * A Test Factory is used to set up the infrastructure for a tests, based on a
 * specific repository implementation to test.
 */
class Legacy extends SetupFactory
{
    /**
     * Data source name.
     */
    protected static string $dsn;

    /**
     * Root dir for IO operations.
     */
    protected static string $ioRootDir;

    /**
     * Database type (sqlite, mysql, ...).
     */
    protected static string $db;

    /**
     * Service container.
     *
     * @var \Ibexa\Core\Base\ServiceContainer
     */
    protected static ServiceContainer $serviceContainer;

    /**
     * If the DB schema has already been initialized.
     */
    protected static bool $schemaInitialized = false;

    /**
     * Cached in-memory initial database data fixture.
     */
    private static ?YamlFixture $initialDataFixture = null;

    protected string $repositoryReference = 'ibexa.api.repository';

    private Connection $connection;

    /**
     * Creates a new setup factory.
     */
    public function __construct()
    {
        self::$dsn = $this->buildDSN();

        if ($repositoryReference = getenv('REPOSITORY_SERVICE_ID')) {
            $this->repositoryReference = $repositoryReference;
        }

        self::$db = $this->getDbType(self::$dsn);

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
     * @throws \Doctrine\DBAL\Exception
     */
    public function getRepository(bool $initializeFromScratch = true): Repository
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
     * @throws \Exception if $configKey could not be found.
     */
    public function getConfigValue(string $configKey): mixed
    {
        return $this->getServiceContainer()->getParameter($configKey);
    }

    public function getIdManager(): Php
    {
        return new Php();
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

    protected function cleanupVarDir(string $sourceDir): void
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
     * Clears internal in memory caches after inserting data circumventing the API.
     */
    protected function clearInternalCaches(): void
    {
        /** @var \Ibexa\Core\Persistence\Legacy\Handler $handler */
        $handler = $this->getServiceContainer()->get(Handler::class);

        $contentLanguageHandler = $handler->contentLanguageHandler();
        if ($contentLanguageHandler instanceof CachingLanguageHandler) {
            $contentLanguageHandler->clearCache();
        }

        $contentTypeHandler = $handler->contentTypeHandler();
        if ($contentTypeHandler instanceof CachingContentTypeHandler) {
            $contentTypeHandler->clearCache();
        }

        /** @var \Psr\Cache\CacheItemPoolInterface $cachePool */
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
     */
    private function getDatabaseConnection(): Connection
    {
        if (!isset($this->connection)) {
            $connection = $this->getServiceContainer()->get('ibexa.persistence.connection');
            if (!$connection instanceof Connection) {
                throw new \LogicException(
                    sprintf('The service "ibexa.persistence.connection" must be an instance of %s', Connection::class)
                );
            }
            $this->connection = $connection;
        }

        return $this->connection;
    }

    /**
     * Returns the service container used for initialization of the repository.
     *
     * @throws \Exception
     */
    public function getServiceContainer(): ServiceContainer
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
                self::getCacheDir(),
                true,
                true
            );
        }

        return self::$serviceContainer;
    }

    /**
     * This is intended to be used from external repository to enable container customization.
     *
     * @param \Symfony\Component\DependencyInjection\ContainerBuilder $containerBuilder
     */
    protected function externalBuildContainer(ContainerBuilder $containerBuilder): void
    {
        // Does nothing by default
    }

    /**
     * Get the Database name.
     */
    public function getDB(): string
    {
        return self::$db;
    }

    /**
     * Apply automatic configuration to needed Symfony Services.
     *
     * Note: Based on
     * {@see \Ibexa\Bundle\Core\DependencyInjection\IbexaCoreExtension::registerForAutoConfiguration},
     * but only for services needed by integration test setup.
     */
    private function registerForAutoConfiguration(ContainerBuilder $containerBuilder): void
    {
        $containerBuilder->registerForAutoconfiguration(FilteringCriterionQueryBuilder::class)
            ->addTag(ServiceTags::FILTERING_CRITERION_QUERY_BUILDER);

        $containerBuilder->registerForAutoconfiguration(FilteringSortClauseQueryBuilder::class)
            ->addTag(ServiceTags::FILTERING_SORT_CLAUSE_QUERY_BUILDER);
    }

    /**
     * @deprecated since Ibexa 4.0, rewrite a test case to use {@see \Ibexa\Contracts\Core\Test\IbexaKernelTestCase} instead.
     */
    public static function getInstallationDir(): string
    {
        // package root directory:
        $installationDir = realpath(__DIR__ . '/../../../../../');
        if (false === $installationDir) {
            throw new \LogicException('Unable to determine installation directory');
        }

        return $installationDir;
    }

    /**
     * @deprecated since Ibexa 4.0, rewrite a test case to use {@see \Ibexa\Contracts\Core\Test\IbexaKernelTestCase} instead.
     */
    public static function getCacheDir(): string
    {
        return self::getInstallationDir() . '/var/cache';
    }

    private function buildDSN(): string
    {
        $dsn = getenv('DATABASE_URL');
        if (false === $dsn) {
            // use sqlite in-memory by default (does not need special handling for paratest as it's per process)
            $dsn = 'sqlite://:memory:';
        } elseif (getenv('TEST_TOKEN') !== false) {
            // Using paratest, assuming dsn ends with db name here...
            $dsn .= '_' . getenv('TEST_TOKEN');
        }

        return $dsn;
    }

    private function getDbType(string $dsn): string
    {
        $dbType = preg_replace('(^([a-z]+).*)', '\\1', $dsn);
        if (null === $dbType) {
            throw  new \LogicException("Failed to extract db type from the dsn: '$dsn'");
        }

        return $dbType;
    }
}
