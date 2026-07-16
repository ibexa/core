<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Bundle\RepositoryInstaller\Migration;

use DateTimeImmutable;
use Ibexa\Contracts\DoctrineMigrations\Migrations\AbstractVersion;
use Ibexa\Contracts\DoctrineMigrations\Migrations\IbexaMigrationInterface;

/**
 * Imports the core Ibexa bootstrap data (default languages, sections, roles, ...), used by
 * {@see \Ibexa\Bundle\RepositoryInstaller\Installer\CoreInstaller} to install a fresh database.
 *
 * Tagged with {@see \Ibexa\Contracts\DoctrineMigrations\Migrations\IbexaMigrationTag::TAG} (see services.yml) so
 * it is discoverable by the Doctrine Migrations pipeline like any other Ibexa migration. Its target version and
 * creation date place it right after {@see InstallSchemaMigration} in {@see \Ibexa\Bundle\DoctrineMigrations\Comparator\IbexaMigrationComparator}
 * order, since it inserts data into the tables that migration creates.
 */
final class ImportDataMigration extends AbstractVersion implements IbexaMigrationInterface
{
    public function getDescription(): string
    {
        return 'Imports the core Ibexa bootstrap data';
    }

    public static function getTargetVersion(): string
    {
        return '4.6.0';
    }

    public static function getCreationDate(): DateTimeImmutable
    {
        return new DateTimeImmutable('2026-07-16 00:00:01');
    }

    protected function getYamlFilePath(): string
    {
        return __DIR__ . '/../Resources/migrations/import_data.yaml';
    }
}
