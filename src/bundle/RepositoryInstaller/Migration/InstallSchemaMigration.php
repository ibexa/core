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
 * Creates the core Ibexa database schema, used by {@see \Ibexa\Bundle\RepositoryInstaller\Installer\CoreInstaller}
 * to install a fresh database.
 *
 * Tagged with {@see \Ibexa\Contracts\DoctrineMigrations\Migrations\IbexaMigrationTag::TAG} (see services.yml) so
 * it is discoverable by the Doctrine Migrations pipeline like any other Ibexa migration. It is also run directly
 * by {@see \Ibexa\Bundle\RepositoryInstaller\Installer\CoreInstaller}, which records its execution in the same
 * migrations versioning table so it isn't re-applied by a later `doctrine:migrations:migrate` run.
 */
final class InstallSchemaMigration extends AbstractVersion implements IbexaMigrationInterface
{
    public function getDescription(): string
    {
        return 'Creates the core Ibexa database schema';
    }

    public static function getTargetVersion(): string
    {
        return '4.6.0';
    }

    public static function getCreationDate(): DateTimeImmutable
    {
        return new DateTimeImmutable('2026-07-16 00:00:00');
    }

    protected function getYamlFilePath(): string
    {
        return __DIR__ . '/../Resources/migrations/install_schema.yaml';
    }
}
