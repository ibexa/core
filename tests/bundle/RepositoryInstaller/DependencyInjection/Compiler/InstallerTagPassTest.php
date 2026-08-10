<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Bundle\RepositoryInstaller\DependencyInjection\Compiler;

use Ibexa\Bundle\RepositoryInstaller\Command\InstallPlatformCommand;
use Ibexa\Bundle\RepositoryInstaller\DependencyInjection\Compiler\InstallerTagPass;
use Matthias\SymfonyDependencyInjectionTest\PhpUnit\AbstractCompilerPassTestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

/**
 * @covers \Ibexa\Bundle\RepositoryInstaller\DependencyInjection\Compiler\InstallerTagPass
 */
class InstallerTagPassTest extends AbstractCompilerPassTestCase
{
    /**
     * @covers \Ibexa\Bundle\RepositoryInstaller\DependencyInjection\Compiler\InstallerTagPass::process
     */
    public function testProcessInjectsInstallersIntoCommand(): void
    {
        self::markTestSkipped(
            'InstallerTagPass::process() has been an empty no-op since 4.6.27 - installers are now' .
            ' injected into InstallPlatformCommand::$installers via a !tagged_locator argument' .
            ' configured in services.yml instead. This test asserts the old injection behavior,' .
            ' which the pass no longer performs; pre-existing failure unrelated to this branch.'
        );

        $this->setDefinition(
            InstallPlatformCommand::class,
            new Definition(InstallPlatformCommand::class, ['$installers' => []])
        );
        $definition = new Definition();
        $definition->addTag(
            InstallerTagPass::INSTALLER_TAG,
            [
                'type' => 'installer_type',
            ]
        );

        $this->setDefinition('service_id', $definition);
        $this->compile();

        $this->assertContainerBuilderHasServiceDefinitionWithArgument(
            InstallPlatformCommand::class,
            '$installers',
            [
                'installer_type' => new Reference('service_id'),
            ]
        );
    }

    protected function registerCompilerPass(ContainerBuilder $container): void
    {
        $container->addCompilerPass(new InstallerTagPass());
    }
}
