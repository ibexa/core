<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Bundle\RepositoryInstaller\Migration;

use Doctrine\Migrations\DependencyFactory;
use Doctrine\Migrations\Metadata\MigrationPlanList;
use Doctrine\Migrations\MigratorConfiguration;
use Ibexa\Bundle\RepositoryInstaller\Migration\Exception\MigrationFailedException;
use Ibexa\Contracts\DoctrineMigrations\Migrations\IbexaOnlyDependencyFactory;
use Ibexa\Contracts\DoctrineSchema\SchemaAssetsFilterBypassInterface;
use Throwable;

/**
 * Runs every not-yet-executed migration tagged with
 * {@see \Ibexa\Contracts\DoctrineMigrations\Migrations\IbexaMigrationTag::TAG} (core's own
 * {@see InstallSchemaMigration} plus any other package's), via the
 * {@see IbexaOnlyDependencyFactory::SERVICE_ID} service - an independent
 * {@see DependencyFactory} that always runs against "ibexa.persistence.connection" and whose
 * MigrationsRepository only ever serves Ibexa-tagged migrations, regardless of what the
 * application's own "doctrine.migrations.dependency_factory" is configured with. Since installing
 * Ibexa DXP itself must never accidentally execute the project's own, user-defined migrations,
 * this is safer than using the shared application DependencyFactory directly.
 *
 * Each migration's execution is recorded in the same versioning table `doctrine:migrations:migrate`
 * would use, so migrations already executed in a prior run are skipped rather than re-applied.
 *
 * Migrations are executed by {@see DependencyFactory::getMigrator()} - the same Migrator
 * "ibexa:doctrine:migrations:migrate" uses - so an install runs them exactly as an upgrade does:
 * each in its own transaction unless {@see \Doctrine\Migrations\AbstractMigration::isTransactional()}
 * says otherwise, against a schema introspected from the live database, with the same events and
 * logging. Doctrine Migrations marks the Migrator interface and MigratorConfiguration `@internal`,
 * so they're used here only.
 *
 * This service isn't registered at all when "ibexa/doctrine-migrations" isn't installed/enabled
 * ({@see \Ibexa\Bundle\RepositoryInstaller\DependencyInjection\Compiler\RemoveTaggedMigrationsRunnerPass}
 * removes its definition), so callers should depend on it as an optional (nullable) service rather
 * than expecting this class itself to handle that unavailability.
 */
final class TaggedMigrationsRunner
{
    private DependencyFactory $dependencyFactory;

    private SchemaAssetsFilterBypassInterface $schemaAssetsFilterBypass;

    public function __construct(
        DependencyFactory $dependencyFactory,
        SchemaAssetsFilterBypassInterface $schemaAssetsFilterBypass
    ) {
        $this->dependencyFactory = $dependencyFactory;
        $this->schemaAssetsFilterBypass = $schemaAssetsFilterBypass;
    }

    /**
     * @return \Doctrine\Migrations\Query\Query[] All SQL statements that were executed, across all migrations run
     *
     * @throws \Ibexa\Bundle\RepositoryInstaller\Migration\Exception\MigrationFailedException naming the migration that failed, with the original error as its previous exception
     */
    public function run(): array
    {
        $metadataStorage = $this->dependencyFactory->getMetadataStorage();
        // Mirrors what the "doctrine:migrations:migrate" console command does before migrating.
        $metadataStorage->ensureInitialized();

        $planCalculator = $this->dependencyFactory->getMigrationPlanCalculator();
        // getMigrations() returns an already-sorted list; its last item is the "latest" version.
        $availableMigrations = $planCalculator->getMigrations()->getItems();
        if ($availableMigrations === []) {
            return [];
        }

        $latestVersion = end($availableMigrations)->getVersion();
        $plan = $planCalculator->getPlanUntilVersion($latestVersion);

        $connection = $this->dependencyFactory->getConnection();
        $migrator = $this->dependencyFactory->getMigrator();
        $migratorConfiguration = new MigratorConfiguration();

        $executedQueries = [];
        foreach ($plan->getItems() as $migrationPlan) {
            // One migration per migrate() call, so that a failure can name the migration it came
            // from - Doctrine's executor logs it, but rethrows the original error as it was.
            try {
                // With the connection's schema assets filter lifted: it hides every table without an
                // ORM entity behind it (i.e. nearly all of them) from the schema the executor
                // introspects, which would make migrations' hasTable() guards think they don't exist.
                /** @var array<string, \Doctrine\Migrations\Query\Query[]> $queriesByVersion */
                $queriesByVersion = $this->schemaAssetsFilterBypass->call(
                    $connection,
                    static fn (): array => $migrator->migrate(
                        new MigrationPlanList([$migrationPlan], $plan->getDirection()),
                        $migratorConfiguration
                    )
                );
            } catch (Throwable $e) {
                throw new MigrationFailedException((string)$migrationPlan->getVersion(), $e);
            }

            foreach ($queriesByVersion as $queries) {
                foreach ($queries as $query) {
                    $executedQueries[] = $query;
                }
            }
        }

        return $executedQueries;
    }
}
