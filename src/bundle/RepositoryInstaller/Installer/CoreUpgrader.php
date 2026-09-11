<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Bundle\RepositoryInstaller\Installer;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception\DriverException;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\SchemaConfig;
use Ibexa\Contracts\DoctrineSchema\Builder\SchemaBuilderInterface;
use Ibexa\Contracts\DoctrineSchema\SchemaImporterInterface;
use Symfony\Component\Console\Helper\ProgressBar;

/**
 * Installer which uses SchemaBuilder.
 */
class CoreUpgrader extends DbBasedInstaller implements Installer
{
    protected SchemaImporterInterface $schemaImporter;

    private array $defaultTableOptions = [];

    public function __construct(Connection $db, SchemaImporterInterface $schemaImporter, string $previousType='ibexa-oss', string $nextType='ibexa-content')
    {
        parent::__construct($db);

        $this->schemaImporter = $schemaImporter;
        //$this->defaultTableOptions = $defaultTableOptions;
        $this->previousType = $previousType;
        $this->nextType = $nextType;
    }

    /**
     * Import Schema using schema.yaml files from new bundles
     *
     * @throws \Ibexa\Contracts\DoctrineSchema\Exception\InvalidConfigurationException
     * @throws \Doctrine\DBAL\DBALException
     */
    public function importSchema()
    {
        $schemaFiles = $this->findSchemaFiles();

        $config = new SchemaConfig();
        $config->setDefaultTableOptions($this->defaultTableOptions);

        $schema = new Schema([], [], $config);

        $this->output->writeln(
            sprintf(
                '<info>Import %d schema files</info>',
                count($schemaFiles)
            )
        );

        foreach ($schemaFiles as $schemaFilePath) {
            $this->schemaImporter->importFromFile($schemaFilePath, $schema);
        }

        $databasePlatform = $this->db->getDatabasePlatform();
        $queries = array_map(function($query) {
            return str_replace('CREATE TABLE', 'CREATE TABLE IF NOT EXISTS', $query);
        }, $schema->toSql($databasePlatform));

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
            try {
                @$this->db->exec($query);
            } catch (DriverException $driverException) {
                if (false === strpos($query, 'ALTER TABLE')) {
                    throw $driverException;
                }
            }
            $progressBar->advance(1);
        }

        $progressBar->finish();
        // go to the next line after ProgressBar::finish and add one more extra blank line for readability
        $this->output->writeln(PHP_EOL);
        // clear any leftover progress bar parts in the output buffer
        $progressBar->clear();
    }

    /**
     * @todo Have a nicer way to retrieve those schemas than this dirty hack
     *
     * @return string[]
     */
    private function findSchemaFiles(): array {
        $schemaFiles = [];

        $previousEditionPackage = str_replace('-', '/', $this->previousType);
        $version=null;
        foreach(json_decode(file_get_contents('vendor/composer/installed.json'), true)['packages'] as $package) {
            if ($package['name'] === $previousEditionPackage) {
                $version = $package['version'];
                break;
            }
        }
        if (is_null($version)) {
            return $schemaFiles; //TODO: Throw a proper error instead
        }
        $nextEditionPackage = str_replace('-', '/', $this->nextType);
        $nextPackageList = [];
        foreach (json_decode(shell_exec("curl -s https://raw.githubusercontent.com/$nextEditionPackage/$version/composer.json"), true)['require'] as $requiredPackage=>$requiredVersion) {
            if (0 === strpos($requiredPackage, 'ibexa/') && $requiredPackage !== $nextEditionPackage) {
                $nextPackageList[] = $requiredPackage;
            }
        }

        foreach($nextPackageList as $package) {
            foreach (['', 'storage/', 'storage/legacy/'] as $subDir) {
                $schemaFile="vendor/$package/src/bundle/Resources/config/{$subDir}schema.yaml";
                if (file_exists($schemaFile)) {
                    $schemaFiles[] = $schemaFile;
                }
            }
        }

        return $schemaFiles;
    }

    public function importData()
    {
        // Already imported during previous install
        //$this->runQueriesFromFile($this->getKernelSQLFileForDBMS('cleandata.sql'));
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

    /**
     * {@inheritdoc}
     */
    public function createConfiguration()
    {
    }
}

class_alias(CoreUpgrader::class, 'EzSystems\PlatformInstallerBundle\Installer\CoreUpgrader');
