<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Bundle\RepositoryInstaller\Installer;

use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Schema\Schema;
use Ibexa\Bundle\RepositoryInstaller\Installer\CoreInstaller;
use Ibexa\Contracts\DoctrineSchema\Builder\SchemaBuilderInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Bridge\PhpUnit\ExpectDeprecationTrait;
use Symfony\Component\Console\Output\BufferedOutput;

/**
 * @covers \Ibexa\Bundle\RepositoryInstaller\Installer\CoreInstaller
 */
final class CoreInstallerTest extends TestCase
{
    use ExpectDeprecationTrait;

    private const string DEPRECATION_MESSAGE = 'Running "ibexa:install" without the "ibexa.installer.doctrine_migrations" '
        . 'setting enabled is deprecated and won\'t be possible in 6.0. Enable it to install the database schema '
        . 'and the core bootstrap data by running the Doctrine Migrations provided by "ibexa/doctrine-migrations".';

    /**
     * @group legacy
     */
    public function testImportSchemaWithoutDoctrineMigrationsTriggersDeprecation(): void
    {
        $schemaBuilder = self::createStub(SchemaBuilderInterface::class);
        $schemaBuilder->method('buildSchema')->willReturn(new Schema());

        $installer = new CoreInstaller(
            DriverManager::getConnection(['driver' => 'pdo_sqlite', 'memory' => true]),
            $schemaBuilder,
            false
        );
        $output = new BufferedOutput();
        $installer->setOutput($output);

        $this->expectDeprecation('Since ibexa/core 5.0.12: ' . self::DEPRECATION_MESSAGE);

        $installer->importSchema();

        self::assertStringContainsString(
            'Deprecated since ibexa/core 5.0.12: ' . self::DEPRECATION_MESSAGE,
            str_replace(PHP_EOL, ' ', $output->fetch())
        );
    }
}
