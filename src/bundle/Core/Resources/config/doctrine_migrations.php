<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

use Ibexa\Bundle\RepositoryInstaller\Migration\AddContentVersionUniqueIndexMigration;
use Ibexa\Bundle\RepositoryInstaller\Migration\AddUrlAliasMlLinkIndexMigration;
use Ibexa\Bundle\RepositoryInstaller\Migration\ConvertMariaDbJsonColumnsMigration;
use Ibexa\Bundle\RepositoryInstaller\Migration\ConvertMariaDbJsonColumnsTo6_0Migration;
use Ibexa\Bundle\RepositoryInstaller\Migration\ConvertPostgreSqlSerialColumnsToIdentityMigration;
use Ibexa\Bundle\RepositoryInstaller\Migration\FixLegacyIdentifiersMigration;
use Ibexa\Bundle\RepositoryInstaller\Migration\ImportDataMigration;
use Ibexa\Bundle\RepositoryInstaller\Migration\InstallSchemaMigration;
use Ibexa\Bundle\RepositoryInstaller\Migration\RecreateMariaDbBookmarkForeignKeysMigration;
use Ibexa\Bundle\RepositoryInstaller\Migration\RenamePostgreSqlPrimaryKeysMigration;
use Ibexa\Bundle\RepositoryInstaller\Migration\RenameSchemaTo5_0Migration;
use Ibexa\Contracts\DoctrineMigrations\Migrations\IbexaMigrationTag;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $configurator): void {
    $services = $configurator->services();

    $services
        ->defaults()
        ->autowire()
        ->autoconfigure(false)
        ->private();

    $services->set(InstallSchemaMigration::class)
        ->arg('$connection', service('ibexa.persistence.connection'))
        ->tag(IbexaMigrationTag::TAG);

    $services->set(AddUrlAliasMlLinkIndexMigration::class)
        ->arg('$connection', service('ibexa.persistence.connection'))
        ->tag(IbexaMigrationTag::TAG);

    $services->set(RenameSchemaTo5_0Migration::class)
        ->arg('$connection', service('ibexa.persistence.connection'))
        ->tag(IbexaMigrationTag::TAG);

    $services->set(ConvertMariaDbJsonColumnsMigration::class)
        ->arg('$connection', service('ibexa.persistence.connection'))
        ->tag(IbexaMigrationTag::TAG);

    $services->set(ImportDataMigration::class)
        ->arg('$connection', service('ibexa.persistence.connection'))
        ->tag(IbexaMigrationTag::TAG);

    $services->set(FixLegacyIdentifiersMigration::class)
        ->arg('$connection', service('ibexa.persistence.connection'))
        ->tag(IbexaMigrationTag::TAG);

    $services->set(RenamePostgreSqlPrimaryKeysMigration::class)
        ->arg('$connection', service('ibexa.persistence.connection'))
        ->tag(IbexaMigrationTag::TAG);

    $services->set(RecreateMariaDbBookmarkForeignKeysMigration::class)
        ->arg('$connection', service('ibexa.persistence.connection'))
        ->tag(IbexaMigrationTag::TAG);

    $services->set(AddContentVersionUniqueIndexMigration::class)
        ->arg('$connection', service('ibexa.persistence.connection'))
        ->tag(IbexaMigrationTag::TAG);

    $services->set(ConvertMariaDbJsonColumnsTo6_0Migration::class)
        ->arg('$connection', service('ibexa.persistence.connection'))
        ->tag(IbexaMigrationTag::TAG);

    $services->set(ConvertPostgreSqlSerialColumnsToIdentityMigration::class)
        ->arg('$connection', service('ibexa.persistence.connection'))
        ->tag(IbexaMigrationTag::TAG);
};
