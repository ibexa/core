<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Integration\Installer\RepositoryInstaller;

use Doctrine\DBAL\Connection;
use Ibexa\Bundle\RepositoryInstaller\Installer\CoreInstaller;
use Ibexa\Tests\Integration\RepositoryInstaller\TestCase;
use Symfony\Component\Console\Output\BufferedOutput;

final class CoreInstallerTest extends TestCase
{
    private CoreInstaller $installer;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->installer = self::getServiceByClassName(CoreInstaller::class);
    }

    public function testImportSchemaAndDataRunsTaggedMigrations(): void
    {
        $this->installer->setOutput(new BufferedOutput());
        $this->installer->importSchema();
        $this->installer->importData();

        $connection = self::getServiceByClassName(Connection::class);

        self::assertTrue($connection->createSchemaManager()->tablesExist(['ibexa_content']));
        self::assertSame(
            'admin',
            $connection->fetchOne('SELECT login FROM ibexa_user WHERE contentobject_id = 14')
        );
    }

    public function testImportSchemaTwiceSkipsAlreadyExecutedMigrations(): void
    {
        $this->installer->setOutput(new BufferedOutput());
        $this->installer->importSchema();

        $output = new BufferedOutput();
        $this->installer->setOutput($output);
        $this->installer->importSchema();

        self::assertStringContainsString('Executed 0 queries', $output->fetch());
    }
}
