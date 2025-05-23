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
use Ibexa\Contracts\DoctrineSchema\Builder\SchemaBuilderInterface;
use Symfony\Component\Console\Helper\ProgressBar;

/**
 * Installer which uses SchemaBuilder.
 */
class CoreInstaller extends DbBasedInstaller implements Installer
{
    /** @var \Ibexa\Contracts\DoctrineSchema\Builder\SchemaBuilderInterface */
    protected $schemaBuilder;

    /**
     * @param \Doctrine\DBAL\Connection $db
     * @param \Ibexa\Contracts\DoctrineSchema\Builder\SchemaBuilderInterface $schemaBuilder
     */
    public function __construct(Connection $db, SchemaBuilderInterface $schemaBuilder)
    {
        parent::__construct($db);

        $this->schemaBuilder = $schemaBuilder;
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
        // note: schema is built using Schema Builder event-driven API
        $schema = $this->schemaBuilder->buildSchema();
        $databasePlatform = $this->db->getDatabasePlatform();
        $queries = array_merge(
            $this->getDropSqlStatementsForExistingSchema($schema, $databasePlatform),
            // generate schema DDL queries
            $schema->toSql($databasePlatform)
        );

        $queriesCount = count($queries);
        $this->output->writeln(
            sprintf(
                '<info>Executing %d queries on database <comment>%s</comment> (<comment>%s</comment>)</info>',
                $queriesCount,
                $this->db->getDatabase(),
                $databasePlatform->getName()
            )
        );
        $progressBar = new ProgressBar($this->output);
        $progressBar->start($queriesCount);

        foreach ($queries as $query) {
            $this->db->executeStatement($query);
            $progressBar->advance(1);
        }

        $progressBar->finish();
        // go to the next line after ProgressBar::finish and add one more extra blank line for readability
        $this->output->writeln(PHP_EOL);
        // clear any leftover progress bar parts in the output buffer
        $progressBar->clear();
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
