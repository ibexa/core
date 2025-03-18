<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Bundle\IO\DependencyInjection\ConfigurationFactory;

use Ibexa\Tests\Bundle\IO\DependencyInjection\ConfigurationFactoryTest;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

abstract class BaseFlysystemTest extends ConfigurationFactoryTest
{
    private string $flysystemAdapterServiceId = 'oneup_flysystem.test_adapter';

    private string $filesystemServiceId = 'ezpublish.core.io.flysystem.my_test_handler_filesystem';

    public function provideHandlerConfiguration()
    {
        $this->setDefinition($this->flysystemAdapterServiceId, new Definition());

        return [
            'adapter' => 'test',
        ];
    }

    public function provideParentServiceDefinition()
    {
        return new Definition(null, [null]);
    }

    public function validateConfiguredHandler($handlerDefinitionId): void
    {
        self::assertContainerBuilderHasServiceDefinitionWithArgument(
            $handlerDefinitionId,
            0,
            new Reference($this->filesystemServiceId)
        );
    }

    public function validateConfiguredContainer(): void
    {
        self::assertContainerBuilderHasService(
            $this->filesystemServiceId
        );
        self::assertContainerBuilderHasServiceDefinitionWithArgument(
            'ezpublish.core.io.flysystem.my_test_handler_filesystem',
            0,
            new Reference($this->flysystemAdapterServiceId)
        );
    }
}
