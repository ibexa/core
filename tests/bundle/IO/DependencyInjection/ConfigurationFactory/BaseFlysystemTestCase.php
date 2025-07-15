<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Bundle\IO\DependencyInjection\ConfigurationFactory;

use Ibexa\Tests\Bundle\IO\DependencyInjection\ConfigurationFactoryTestCase;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

abstract class BaseFlysystemTestCase extends ConfigurationFactoryTestCase
{
    private string $flysystemAdapterServiceId = 'oneup_flysystem.test_adapter';

    private string $filesystemServiceId = 'ibexa.core.io.flysystem.my_test_handler_filesystem';

    public function provideHandlerConfiguration(): array
    {
        $this->setDefinition($this->flysystemAdapterServiceId, new Definition());

        return [
            'adapter' => 'test',
        ];
    }

    public function provideParentServiceDefinition(): Definition
    {
        return new Definition(null, [null]);
    }

    public function validateConfiguredHandler(string $handlerServiceId): void
    {
        $this->assertContainerBuilderHasServiceDefinitionWithArgument(
            $handlerServiceId,
            0,
            new Reference($this->filesystemServiceId)
        );
    }
}
