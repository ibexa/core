<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Bundle\Core\DependencyInjection\Compiler;

use Ibexa\Core\FieldType\BinaryFile\BinaryFileStorage;
use Ibexa\Core\FieldType\Media\MediaStorage;
use Ibexa\Core\MVC\Symfony\FieldType\BinaryBase\ContentDownloadUrlGenerator;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class BinaryContentDownloadPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (!$container->has(ContentDownloadUrlGenerator::class)) {
            return;
        }

        $downloadUrlReference = new Reference(ContentDownloadUrlGenerator::class);

        $this->addCall($container, $downloadUrlReference, MediaStorage::class);
        $this->addCall($container, $downloadUrlReference, BinaryFileStorage::class);
    }

    private function addCall(ContainerBuilder $container, Reference $reference, string $targetServiceName): void
    {
        if (!$container->has($targetServiceName)) {
            return;
        }

        $definition = $container->findDefinition($targetServiceName);
        $definition->addMethodCall('setDownloadUrlGenerator', [$reference]);
    }
}
