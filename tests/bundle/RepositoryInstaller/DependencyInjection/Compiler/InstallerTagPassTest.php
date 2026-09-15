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
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

#[CoversClass(InstallerTagPass::class)]
#[CoversMethod(InstallerTagPass::class, 'process')]
class InstallerTagPassTest extends AbstractCompilerPassTestCase
{
    public function testProcessInjectsInstallersIntoCommand(): void
    {
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
