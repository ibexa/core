<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Bundle\Core\DependencyInjection\Compiler;

use Ibexa\Bundle\Core\DependencyInjection\Compiler\SessionConfigurationPass;
use Matthias\SymfonyDependencyInjectionTest\PhpUnit\AbstractCompilerPassTestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpFoundation\Session\Storage\Handler\AbstractSessionHandler;
use Symfony\Component\HttpFoundation\Session\Storage\Handler\SessionHandlerFactory;

class SessionConfigurationPassTest extends AbstractCompilerPassTestCase
{
    protected function registerCompilerPass(ContainerBuilder $container): void
    {
        $container->addCompilerPass(new SessionConfigurationPass());
    }

    public function testCompilesWithoutStorageDefinitions(): void
    {
        $this->assertContainerBuilderNotHasService('session.storage.native');
        $this->assertContainerBuilderNotHasService('session.storage.php_bridge');
        $this->assertContainerBuilderNotHasService('session.storage.factory.native');
        $this->assertContainerBuilderNotHasService('session.storage.factory.php_bridge');

        $this->doCompile();
    }

    public function testCompileUsingStorageFactory(): void
    {
        $this->container->setDefinition(
            'session.storage.factory.native',
            (new Definition())->setArguments([null, null, null])
        );
        $this->container->setDefinition(
            'session.storage.factory.php_bridge',
            (new Definition())->setArguments([null, null])
        );

        $this->doCompile();

        $this->assertContainerBuilderHasServiceDefinitionWithArgument(
            'session.storage.factory.native',
            1,
            new Reference('session.handler')
        );
        $this->assertContainerBuilderHasServiceDefinitionWithArgument(
            'session.storage.factory.php_bridge',
            0,
            new Reference('session.handler')
        );
    }

    public function testCompileUsingStorage(): void
    {
        $this->container->setDefinition(
            'session.storage.native',
            (new Definition())->setArguments([null, null, null])
        );
        $this->container->setDefinition(
            'session.storage.php_bridge',
            (new Definition())->setArguments([null, null])
        );

        $this->doCompile();

        $this->assertContainerBuilderHasServiceDefinitionWithArgument(
            'session.storage.native',
            1,
            new Reference('session.handler')
        );
        $this->assertContainerBuilderHasServiceDefinitionWithArgument(
            'session.storage.php_bridge',
            0,
            new Reference('session.handler')
        );
    }

    private function doCompile(): void
    {
        $this->container->setParameter('ibexa.session.handler_id', 'my_handler');
        $this->container->setParameter('ibexa.session.save_path', 'my_save_path');

        $this->compile();

        $this->assertContainerBuilderHasAlias('session.handler', 'my_handler');
        $this->assertContainerBuilderHasParameter('session.save_path', 'my_save_path');
    }

    public function testCompileWithDsn(): void
    {
        $dsn = 'redis://instance.local:1234';

        $definition = new Definition(AbstractSessionHandler::class);
        $definition->setFactory([SessionHandlerFactory::class, 'createHandler']);
        $definition->setArguments([$dsn]);

        $this->container->setDefinition('session.abstract_handler', $definition);
        $this->container->setParameter('ibexa.session.handler_id', $dsn);
        $this->container->setDefinition(
            'session.storage.native',
            (new Definition())->setArguments([null, null, null])
        );
        $this->container->setDefinition(
            'session.storage.php_bridge',
            (new Definition())->setArguments([null, null])
        );

        $this->compile();

        $this->assertContainerBuilderHasAlias('session.handler', 'session.abstract_handler');
    }

    public function testCompileWithNullValues(): void
    {
        $this->container->setParameter('ibexa.session.handler_id', null);
        $this->container->setParameter('ibexa.session.save_path', null);

        $this->compile();

        $this->assertContainerBuilderNotHasService('session.handler');
        self::assertNotTrue($this->container->hasParameter('session.save_path'));
    }
}
