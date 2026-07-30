<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Bundle\RepositoryInstaller\Migration;

use DateTimeImmutable;
use Doctrine\DBAL\Connection;
use Doctrine\Migrations\DependencyFactory;
use Doctrine\Migrations\Metadata\MigrationPlan;
use Doctrine\Migrations\Metadata\Storage\MetadataStorage;
use Doctrine\Migrations\Version\ExecutionResult;
use Ibexa\Contracts\DoctrineMigrations\Migrations\IbexaOnlyDependencyFactory;

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
 * Execution is driven manually here (rather than via {@see DependencyFactory::getMigrator()}), since
 * Doctrine Migrations' Migrator/Executor/AliasResolver classes are marked `@internal`. Only the
 * DependencyFactory's public accessors ({@see DependencyFactory::getMigrationPlanCalculator()},
 * {@see DependencyFactory::getMetadataStorage()}, {@see DependencyFactory::getConnection()}) are used.
 *
 * This service isn't registered at all when "ibexa/doctrine-migrations" isn't installed/enabled
 * ({@see \Ibexa\Bundle\RepositoryInstaller\DependencyInjection\Compiler\RemoveTaggedMigrationsRunnerPass}
 * removes its definition), so callers should depend on it as an optional (nullable) service rather
 * than expecting this class itself to handle that unavailability.
 */
final class TaggedMigrationsRunner
{
    private DependencyFactory $dependencyFactory;

    public function __construct(DependencyFactory $dependencyFactory)
    {
        $this->dependencyFactory = $dependencyFactory;
    }

    /**
     * @return \Doctrine\Migrations\Query\Query[] All SQL statements that were executed, across all migrations run
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

        $executedQueries = [];
        foreach ($plan->getItems() as $migrationPlan) {
            foreach ($this->executeMigration($connection, $metadataStorage, $migrationPlan) as $query) {
                $executedQueries[] = $query;
            }
        }

        return $executedQueries;
    }

    /**
     * @return \Doctrine\Migrations\Query\Query[]
     */
    private function executeMigration(
        Connection $connection,
        MetadataStorage $metadataStorage,
        MigrationPlan $migrationPlan
    ): array {
        $executedAt = new DateTimeImmutable();

        $migration = $migrationPlan->getMigration();
        // A freshly-constructed, empty Schema() would make every hasTable()/getTable() guard
        // check inside a migration see nothing at all, regardless of what previous migrations
        // in this same run (or a prior run) actually created -- introspect the live database
        // instead, same as what "doctrine:migrations:migrate" itself does via
        // DBALSchemaDiffProvider::createFromSchema().
        $migration->up($connection->getSchemaManager()->createSchema());
        $queries = $migration->getSql();

        foreach ($queries as $query) {
            $connection->executeStatement($query->getStatement(), $query->getParameters(), $query->getTypes());
        }

        $result = new ExecutionResult($migrationPlan->getVersion(), $migrationPlan->getDirection(), $executedAt);
        $metadataStorage->complete($result);

        return $queries;
    }
}
