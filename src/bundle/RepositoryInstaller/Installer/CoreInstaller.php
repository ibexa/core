<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Bundle\RepositoryInstaller\Installer;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\Table;
use Doctrine\Migrations\Query\Query;
use Ibexa\Bundle\RepositoryInstaller\Migration\TaggedMigrationsRunner;
use Ibexa\Contracts\DoctrineMigrations\Migrations\IbexaOnlyDependencyFactory;
use Ibexa\Contracts\DoctrineSchema\Builder\SchemaBuilderInterface;
use Ibexa\Contracts\DoctrineSchema\DbPlatformFactoryInterface;
use Ibexa\Contracts\DoctrineSchema\SchemaAssetsFilterBypassInterface;
use RuntimeException;
use Symfony\Component\Console\Helper\ProgressBar;

/**
 * Installer which uses SchemaBuilder.
 */
class CoreInstaller extends DbBasedInstaller implements Installer
{
    /** @var \Ibexa\Contracts\DoctrineSchema\Builder\SchemaBuilderInterface */
    protected $schemaBuilder;

    private SchemaAssetsFilterBypassInterface $schemaAssetsFilterBypass;

    private DbPlatformFactoryInterface $dbPlatformFactory;

    private bool $schemaBuilderEventEnabled;

    private ?TaggedMigrationsRunner $taggedMigrationsRunner;

    /**
     * @param \Doctrine\DBAL\Connection $db
     * @param \Ibexa\Contracts\DoctrineSchema\Builder\SchemaBuilderInterface $schemaBuilder
     */
    public function __construct(
        Connection $db,
        SchemaBuilderInterface $schemaBuilder,
        SchemaAssetsFilterBypassInterface $schemaAssetsFilterBypass,
        DbPlatformFactoryInterface $dbPlatformFactory,
        bool $schemaBuilderEventEnabled,
        ?TaggedMigrationsRunner $taggedMigrationsRunner = null
    ) {
        parent::__construct($db);

        $this->schemaBuilder = $schemaBuilder;
        $this->schemaAssetsFilterBypass = $schemaAssetsFilterBypass;
        $this->dbPlatformFactory = $dbPlatformFactory;
        $this->schemaBuilderEventEnabled = $schemaBuilderEventEnabled;
        $this->taggedMigrationsRunner = $taggedMigrationsRunner;
    }

    private function getIbexaDatabasePlatform(): AbstractPlatform
    {
        $driverName = $this->db->getParams()['driver'] ?? '';

        return $this->dbPlatformFactory->createDatabasePlatformFromDriverName($driverName)
            ?? $this->db->getDatabasePlatform();
    }

    /**
     * Import Schema using event-driven Schema Builder API from Ibexa DoctrineSchema Bundle.
     *
     * If you wish to extend schema, implement your own EventSubscriber
     *
     * @see \Ibexa\Contracts\DoctrineSchema\Event\SchemaBuilderEvent
     * @see \Ibexa\Bundle\RepositoryInstaller\Event\Subscriber\BuildSchemaSubscriber
     *
     * @throws \Doctrine\DBAL\Exception
     */
    public function importSchema()
    {
        if ($this->schemaBuilderEventEnabled) {
            $this->executeQueries($this->getQueriesFromSchemaBuilderEvent());

            return;
        }

        if ($this->taggedMigrationsRunner === null) {
            throw new RuntimeException(
                'Disabling "ibexa.installer.schema_builder_event.enabled" requires the "' .
                IbexaOnlyDependencyFactory::SERVICE_ID . '" service (provided by "ibexa/doctrine-migrations", ' .
                'with Ibexa\\Bundle\\DoctrineMigrations\\IbexaDoctrineMigrationsBundle registered) to be available.'
            );
        }

        $this->reportExecutedQueries($this->taggedMigrationsRunner->run());
    }

    /**
     * @return list<\Doctrine\Migrations\Query\Query>
     */
    private function getQueriesFromSchemaBuilderEvent(): array
    {
        $schema = $this->schemaBuilder->buildSchema();
        $databasePlatform = $this->getIbexaDatabasePlatform();

        $sqls = array_merge(
            $this->getDropSqlStatementsForExistingSchema($schema, $databasePlatform),
            $schema->toSql($databasePlatform)
        );

        return array_map(static fn (string $sql): Query => new Query($sql), $sqls);
    }

    /**
     * @param list<\Doctrine\Migrations\Query\Query> $queries
     */
    private function executeQueries(array $queries): void
    {
        $queriesCount = count($queries);
        $this->output->writeln(
            sprintf(
                '<info>Executing %d queries on database <comment>%s</comment> (<comment>%s</comment>)</info>',
                $queriesCount,
                $this->db->getDatabase(),
                $this->getDBMSDataDirectoryName()
            )
        );
        $progressBar = new ProgressBar($this->output);
        $progressBar->start($queriesCount);

        foreach ($queries as $query) {
            $this->db->executeStatement($query->getStatement(), $query->getParameters(), $query->getTypes());
            $progressBar->advance(1);
        }

        $progressBar->finish();
        // go to the next line after ProgressBar::finish and add one more extra blank line for readability
        $this->output->writeln(PHP_EOL);
        // clear any leftover progress bar parts in the output buffer
        $progressBar->clear();
    }

    /**
     * @param list<\Doctrine\Migrations\Query\Query> $queries
     */
    private function reportExecutedQueries(array $queries): void
    {
        $this->output->writeln(
            sprintf(
                '<info>Executed %d queries on database <comment>%s</comment> (<comment>%s</comment>)</info>',
                count($queries),
                $this->db->getDatabase(),
                $this->getDBMSDataDirectoryName()
            )
        );
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException
     */
    public function importData()
    {
        $this->runQueriesFromFile($this->getKernelSQLFileForDBMS('cleandata.sql'));
    }

    /**
     * @param \Doctrine\DBAL\Schema\Schema $newSchema
     * @param \Doctrine\DBAL\Platforms\AbstractPlatform $databasePlatform
     *
     * @return string[]
     */
    protected function getDropSqlStatementsForExistingSchema(
        Schema $newSchema,
        AbstractPlatform $databasePlatform
    ): array {
        // Reinstalling needs to see every pre-existing table, including ones
        // with no Doctrine ORM entity behind them, to correctly drop them
        // before recreating the full schema below. Bypass whichever schema
        // assets filter is configured on this connection (e.g.
        // ManagedTablesSchemaAssetFilter, which deliberately hides
        // non-entity tables from doctrine:schema:update) for this one
        // listing.
        $existingTableNames = $this->schemaAssetsFilterBypass->call(
            $this->db,
            fn (): array => array_map(
                static fn (Table $table): string => $table->getName(),
                $this->db->createSchemaManager()->listTables()
            )
        );
        $statements = [];
        // reverse table order for clean-up (due to FKs)
        $tables = array_reverse($newSchema->getTables());
        // cleanup pre-existing database
        foreach ($tables as $table) {
            if (in_array($table->getName(), $existingTableNames, true)) {
                $statements[] = $databasePlatform->getDropTableSQL($table->getName());
            }
        }

        return $statements;
    }

    /**
     * Handle optional import of binary files to var folder.
     */
    public function importBinaries()
    {
    }
}
