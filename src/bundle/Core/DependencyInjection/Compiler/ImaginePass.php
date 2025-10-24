<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Bundle\Core\DependencyInjection\Compiler;

use Ibexa\Bundle\Core\Imagine\Filter\FilterConfiguration;
use Ibexa\Bundle\Core\Imagine\Filter\Gmagick\ReduceNoiseFilter as GmagickReduceNoiseFilter;
use Ibexa\Bundle\Core\Imagine\Filter\Gmagick\SwirlFilter as GmagickSwirlFilter;
use Ibexa\Bundle\Core\Imagine\Filter\Imagick\ReduceNoiseFilter as ImagickReduceNoiseFilter;
use Ibexa\Bundle\Core\Imagine\Filter\Imagick\SwirlFilter as ImagickSwirlFilter;
use Symfony\Component\DependencyInjection\Alias;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class ImaginePass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasDefinition('liip_imagine.filter.configuration')) {
            return;
        }

        $filterConfigDef = $container->findDefinition('liip_imagine.filter.configuration');
        $filterConfigDef->setClass(FilterConfiguration::class);
        $filterConfigDef->addMethodCall('setConfigResolver', [new Reference('ibexa.config.resolver')]);

        if ($container->hasAlias('liip_imagine')) {
            $imagineAlias = (string)$container->getAlias('liip_imagine');
            $driver = substr($imagineAlias, strrpos($imagineAlias, '.') + 1);

            $this->processReduceNoiseFilter($container, $driver);
            $this->processSwirlFilter($container, $driver);
        }
    }

    private function processReduceNoiseFilter(
        ContainerBuilder $container,
        string $driver
    ): void {
        if ($driver === 'imagick') {
            $container->setAlias('ibexa.image_alias.imagine.filter.reduce_noise', new Alias(ImagickReduceNoiseFilter::class));
        } elseif ($driver === 'gmagick') {
            $container->setAlias('ibexa.image_alias.imagine.filter.reduce_noise', new Alias(GmagickReduceNoiseFilter::class));
        }
    }

    private function processSwirlFilter(
        ContainerBuilder $container,
        string $driver
    ): void {
        if ($driver === 'imagick') {
            $container->setAlias('ibexa.image_alias.imagine.filter.swirl', new Alias(ImagickSwirlFilter::class));
        } elseif ($driver === 'gmagick') {
            $container->setAlias('ibexa.image_alias.imagine.filter.swirl', new Alias(GmagickSwirlFilter::class));
        }
    }
}
