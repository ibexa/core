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
use Doctrine\Migrations\Query\Query;
use Ibexa\Bundle\RepositoryInstaller\Migration\TaggedMigrationsRunner;
use Ibexa\Contracts\DoctrineMigrations\Migrations\IbexaOnlyDependencyFactory;
use Ibexa\Contracts\DoctrineSchema\Builder\SchemaBuilderInterface;
use RuntimeException;
use Symfony\Component\Console\Helper\ProgressBar;

/**
 * Installer which creates the core database schema.
 */
class CoreInstaller extends DbBasedInstaller implements Installer
{
    /** @var \Ibexa\Contracts\DoctrineSchema\Builder\SchemaBuilderInterface */
    protected $schemaBuilder;

    private bool $schemaBuilderEventEnabled;

    private ?TaggedMigrationsRunner $taggedMigrationsRunner;

    public function __construct(
        Connection $db,
        SchemaBuilderInterface $schemaBuilder,
        bool $schemaBuilderEventEnabled,
        ?TaggedMigrationsRunner $taggedMigrationsRunner = null
    ) {
        parent::__construct($db);

        $this->schemaBuilder = $schemaBuilder;
        $this->schemaBuilderEventEnabled = $schemaBuilderEventEnabled;
        $this->taggedMigrationsRunner = $taggedMigrationsRunner;
    }

    /**
     * Imports the core database schema.
     *
     * When the "ibexa.installer.schema_builder_event.enabled" setting is enabled (the default), the schema
     * is built by dispatching the legacy event-driven {@see \Ibexa\Contracts\DoctrineSchema\Event\SchemaBuilderEvent},
     * allowing other packages to contribute their own tables via an event subscriber.
     *
     * Otherwise, the schema is installed by {@see TaggedMigrationsRunner}, which runs every not-yet-executed
     * migration tagged with {@see \Ibexa\Contracts\DoctrineMigrations\Migrations\IbexaMigrationTag::TAG}
     * (core's own {@see \Ibexa\Bundle\RepositoryInstaller\Migration\InstallSchemaMigration} plus any other
     * package's) via the application's Doctrine Migrations DependencyFactory.
     *
     * @throws \Doctrine\DBAL\DBALException
     * @throws \RuntimeException if "ibexa.installer.schema_builder_event.enabled" is disabled but
     *     "ibexa/doctrine-migrations" isn't installed/enabled to run the migrations-based path instead
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
                'with Ibexa\Bundle\DoctrineMigrations\IbexaDoctrineMigrationsBundle registered) to be available.'
            );
        }

        $this->reportExecutedQueries($this->taggedMigrationsRunner->run());
    }

    /**
     * Builds the schema using the event-driven Schema Builder API from the Ibexa DoctrineSchema bundle.
     *
     * If you wish to extend the schema, implement your own EventSubscriber.
     *
     * @see \Ibexa\Contracts\DoctrineSchema\Event\SchemaBuilderEvent
     * @see \Ibexa\Bundle\RepositoryInstaller\Event\Subscriber\BuildSchemaSubscriber
     *
     * @return list<\Doctrine\Migrations\Query\Query>
     */
    private function getQueriesFromSchemaBuilderEvent(): array
    {
        $schema = $this->schemaBuilder->buildSchema();
        $databasePlatform = $this->db->getDatabasePlatform();

        $sqls = array_merge(
            $this->getDropSqlStatementsForExistingSchema($schema, $databasePlatform),
            $schema->toSql($databasePlatform)
        );

        return array_map(
            static fn (string $sql): Query => new Query($sql),
            $sqls,
            []
        );
    }

    /**
     * Reports the queries {@see TaggedMigrationsRunner} already executed (and recorded) via the Doctrine
     * Migrations DependencyFactory.
     *
     * @param \Doctrine\Migrations\Query\Query[] $queries
     */
    private function reportExecutedQueries(array $queries): void
    {
        $this->output->writeln(
            sprintf(
                '<info>Executed %d queries on database <comment>%s</comment> (<comment>%s</comment>)</info>',
                count($queries),
                $this->db->getDatabase(),
                $this->db->getDatabasePlatform()->getName()
            )
        );
    }

    /**
     * @param \Doctrine\Migrations\Query\Query[] $queries
     */
    private function executeQueries(array $queries): void
    {
        $queriesCount = count($queries);
        $this->output->writeln(
            sprintf(
                '<info>Executing %d queries on database <comment>%s</comment> (<comment>%s</comment>)</info>',
                $queriesCount,
                $this->db->getDatabase(),
                $this->db->getDatabasePlatform()->getName()
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
     * Imports the core bootstrap data.
     *
     * When the "ibexa.installer.schema_builder_event.enabled" setting is enabled (the default), this imports
     * the DBMS-specific "cleandata.sql" file directly.
     *
     * Otherwise, this is a no-op: {@see \Ibexa\Bundle\RepositoryInstaller\Migration\ImportDataMigration} is
     * tagged and already runs as part of {@see importSchema()}'s call to {@see TaggedMigrationsRunner}.
     *
     * @throws \Doctrine\DBAL\DBALException
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException
     */
    public function importData()
    {
        if ($this->schemaBuilderEventEnabled) {
            $this->runQueriesFromFile($this->getKernelSQLFileForDBMS('cleandata.sql'));
        }
    }

    /**
     * @return list<string>
     */
    protected function getDropSqlStatementsForExistingSchema(
        Schema $newSchema,
        AbstractPlatform $databasePlatform
    ): array {
        $existingSchema = $this->db->getSchemaManager()->createSchema();
        $statements = [];
        // reverse table order for clean-up (due to FKs)
        $tables = array_reverse($newSchema->getTables());
        // cleanup pre-existing database
        foreach ($tables as $table) {
            if ($existingSchema->hasTable($table->getName())) {
                $statements[] = $databasePlatform->getDropTableSQL($table);
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
