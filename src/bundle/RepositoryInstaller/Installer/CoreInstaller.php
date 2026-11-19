<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Bundle\RepositoryInstaller\Installer;

use Doctrine\DBAL\Connection;
use Doctrine\Migrations\Query\Query;
use Ibexa\Bundle\RepositoryInstaller\Migration\Exception\MigrationFailedException;
use Ibexa\Bundle\RepositoryInstaller\Migration\ImportDataMigration;
use Ibexa\Bundle\RepositoryInstaller\Migration\TaggedMigrationsRunner;
use Ibexa\Contracts\DoctrineMigrations\Migrations\IbexaMigrationTag;
use Ibexa\Contracts\DoctrineMigrations\Migrations\IbexaOnlyDependencyFactory;
use RuntimeException;

/**
 * Installer which creates the core database schema.
 */
class CoreInstaller extends DbBasedInstaller implements Installer
{
    public function __construct(
        Connection $db,
        private readonly ?TaggedMigrationsRunner $taggedMigrationsRunner = null
    ) {
        parent::__construct($db);
    }

    /**
     * Imports the core database schema by running every not-yet-executed migration tagged with {@see IbexaMigrationTag::TAG}.
     *
     * @throws RuntimeException if "ibexa/doctrine-migrations" isn't installed/enabled
     * @throws MigrationFailedException if one of those migrations fails
     */
    public function importSchema()
    {
        if ($this->taggedMigrationsRunner === null) {
            throw new RuntimeException(
                'Installing requires the "' . IbexaOnlyDependencyFactory::SERVICE_ID . '" service ' .
                '(provided by "ibexa/doctrine-migrations", with Ibexa\Bundle\DoctrineMigrations\IbexaDoctrineMigrationsBundle ' .
                'registered) to be available.'
            );
        }

        $this->reportExecutedQueries($this->taggedMigrationsRunner->run());
    }

    /**
     * @param Query[] $queries
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
     * No-op, as {@see ImportDataMigration} imports the core bootstrap data as part of {@see importSchema()}.
     */
    public function importData() {}

    /**
     * Handle optional import of binary files to var folder.
     */
    public function importBinaries() {}
}
