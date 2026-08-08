<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Integration\Installer\RepositoryInstaller;

use Ibexa\Bundle\RepositoryInstaller\Installer\CoreInstaller;
use Ibexa\Tests\Integration\RepositoryInstaller\TestCase;
use Symfony\Component\Console\Output\NullOutput;

final class CoreInstallerTest extends TestCase
{
    private CoreInstaller $installer;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->installer = self::getServiceByClassName(CoreInstaller::class);
    }

    public function testImportSchema(): void
    {
        $this->installer->setOutput(new NullOutput());
        $this->installer->importSchema();
    }

    public function testImportSchemaTwiceDoesNotFailOnPreExistingLegacyTables(): void
    {
        $this->installer->setOutput(new NullOutput());
        $this->installer->importSchema();
        // Second run must see (and drop) the legacy tables created above before
        // re-creating them, otherwise CREATE TABLE fails with "already exists".
        $this->installer->importSchema();
    }
}
