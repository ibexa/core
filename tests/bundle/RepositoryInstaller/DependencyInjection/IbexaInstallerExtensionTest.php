<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Bundle\RepositoryInstaller\DependencyInjection;

use Ibexa\Bundle\RepositoryInstaller\Bootstrapper\DoctrineMigrationsSchemaHook;
use Ibexa\Bundle\RepositoryInstaller\Command\InstallPlatformCommand;
use Ibexa\Bundle\RepositoryInstaller\DependencyInjection\IbexaRepositoryInstallerExtension;
use Ibexa\Bundle\RepositoryInstaller\Installer\CoreInstaller;
use Ibexa\Bundle\RepositoryInstaller\Installer\DbBasedInstaller;
use Ibexa\Contracts\Test\Core\Bootstrapper\HookInterface;
use Matthias\SymfonyDependencyInjectionTest\PhpUnit\AbstractExtensionTestCase;

/**
 * @covers \Ibexa\Bundle\RepositoryInstaller\DependencyInjection\IbexaRepositoryInstallerExtension
 */
class IbexaInstallerExtensionTest extends AbstractExtensionTestCase
{
    /**
     * @covers \Ibexa\Bundle\RepositoryInstaller\DependencyInjection\IbexaRepositoryInstallerExtension::load
     */
    public function testLoadLoadsTaggedCoreInstaller(): void
    {
        $this->load();
        $this->assertContainerBuilderHasServiceDefinitionWithParent(
            CoreInstaller::class,
            DbBasedInstaller::class
        );
        $this->assertContainerBuilderHasServiceDefinitionWithTag(
            CoreInstaller::class,
            'ibexa.installer',
            ['type' => 'ibexa-oss']
        );
    }

    /**
     * @covers \Ibexa\Bundle\RepositoryInstaller\DependencyInjection\IbexaRepositoryInstallerExtension::load
     */
    public function testLoadLoadsTaggedInstallerCommand(): void
    {
        $this->load();
        $this->assertContainerBuilderHasServiceDefinitionWithTag(
            InstallPlatformCommand::class,
            'console.command'
        );
    }

    /**
     * @covers \Ibexa\Bundle\RepositoryInstaller\DependencyInjection\IbexaRepositoryInstallerExtension::load
     */
    public function testLoadRegistersTaggedDoctrineMigrationsSchemaHookInTestEnvironment(): void
    {
        $this->container->setParameter('kernel.environment', 'test');

        $this->load();

        $this->assertContainerBuilderHasServiceDefinitionWithTag(
            DoctrineMigrationsSchemaHook::class,
            HookInterface::TAG,
            ['priority' => DoctrineMigrationsSchemaHook::PRIORITY]
        );
    }

    /**
     * @covers \Ibexa\Bundle\RepositoryInstaller\DependencyInjection\IbexaRepositoryInstallerExtension::load
     */
    public function testLoadSkipsDoctrineMigrationsSchemaHookOutsideTestEnvironment(): void
    {
        $this->load();

        self::assertFalse($this->container->hasDefinition(DoctrineMigrationsSchemaHook::class));
    }

    protected function setUp(): void
    {
        parent::setUp();

        // Every extension load() needs this; the two tests above are the ones that care about its
        // value. A real kernel always defines it before any extension is loaded.
        $this->container->setParameter('kernel.environment', 'prod');
    }

    protected function getContainerExtensions(): array
    {
        return [
            new IbexaRepositoryInstallerExtension(),
        ];
    }
}
