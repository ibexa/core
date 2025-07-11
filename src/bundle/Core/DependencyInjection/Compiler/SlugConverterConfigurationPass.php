<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Bundle\Core\DependencyInjection\Compiler;

use Ibexa\Core\Persistence\Legacy\Content\UrlAlias\SlugConverter;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class SlugConverterConfigurationPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (!$container->has(SlugConverter::class)) {
            return;
        }
        $slugConverterDefinition = $container->getDefinition(SlugConverter::class);

        $parameterConfiguration = $slugConverterDefinition->getArgument(1);
        $semanticConfiguration = $container->getParameter('ibexa.url_alias.slug_converter');

        $mergedConfiguration = $parameterConfiguration;

        if (isset($semanticConfiguration['transformation'])) {
            $mergedConfiguration['transformation'] = $semanticConfiguration['transformation'];
        }

        if (isset($semanticConfiguration['separator'])) {
            $mergedConfiguration['wordSeparatorName'] = $semanticConfiguration['separator'];
        }

        $transformationGroups = $parameterConfiguration['transformationGroups'] ?? SlugConverter::DEFAULT_CONFIGURATION['transformationGroups'];

        if (isset($semanticConfiguration['transformation_groups'])) {
            $mergedConfiguration['transformationGroups'] = array_merge_recursive(
                $transformationGroups,
                $semanticConfiguration['transformation_groups']
            );

            foreach ($semanticConfiguration['transformation_groups'] as $group => $groupConfig) {
                if (isset($groupConfig['cleanup_method'])) {
                    $mergedConfiguration['transformationGroups'][$group]['cleanupMethod'] = $groupConfig['cleanup_method'];
                }
            }
        }

        if (isset($mergedConfiguration['transformation']) &&
            !array_key_exists($mergedConfiguration['transformation'], $mergedConfiguration['transformationGroups'])) {
            throw new InvalidConfigurationException(sprintf(
                "Unknown transformation group selected: '%s'.\nAvailable groups: '%s'",
                $mergedConfiguration['transformation'],
                implode(', ', array_keys($mergedConfiguration['transformationGroups']))
            ));
        }

        if (empty($mergedConfiguration['transformation'])) {
            @trigger_error(
                sprintf(
                    'Relying on default url_alias.slug_converter.transformation setting ("%s") is deprecated and might change in the next major. Set it explicitly to one of the following: %s',
                    SlugConverter::DEFAULT_CONFIGURATION['transformation'],
                    implode(', ', array_keys($transformationGroups))
                ),
                E_USER_DEPRECATED
            );
        }

        $slugConverterDefinition->setArgument(1, $mergedConfiguration);
    }
}
