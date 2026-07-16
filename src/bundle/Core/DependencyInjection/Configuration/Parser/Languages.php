<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Bundle\Core\DependencyInjection\Configuration\Parser;

use Ibexa\Bundle\Core\DependencyInjection\Configuration\AbstractParser;
use Ibexa\Bundle\Core\DependencyInjection\Configuration\SiteAccessAware\ContextualizerInterface;
use Symfony\Component\Config\Definition\Builder\NodeBuilder;

class Languages extends AbstractParser
{
    /** @var array<string, string[]> */
    private array $siteAccessesByLanguages = [];

    public function addSemanticConfig(NodeBuilder $nodeBuilder): void
    {
        $nodeBuilder
            ->arrayNode('languages')
                ->requiresAtLeastOneElement()
                ->info('Available languages, in order of precedence')
                ->example(['fre-FR', 'eng-GB'])
                ->prototype('scalar')->end()
            ->end()
            ->arrayNode('translation_siteaccesses')
                ->info('List of "translation siteaccesses" which can be used by language switcher.')
                ->example(['french_siteaccess', 'english_siteaccess'])
                ->prototype('scalar')->end()
            ->end();
    }

    public function preMap(
        array $config,
        ContextualizerInterface $contextualizer
    ): void {
        $contextualizer->mapConfigArray('languages', $config, ContextualizerInterface::UNIQUE);
        $contextualizer->mapConfigArray('translation_siteaccesses', $config, ContextualizerInterface::UNIQUE);

        $container = $contextualizer->getContainer();
        if ($container->hasParameter('ibexa.site_access.by_language')) {
            /** @var array<string, string[]> $siteAccessesByLanguage */
            $siteAccessesByLanguage = $container->getParameter('ibexa.site_access.by_language');
            $this->siteAccessesByLanguages = $siteAccessesByLanguage;
        }
    }

    public function mapConfig(
        array &$scopeSettings,
        $currentScope,
        ContextualizerInterface $contextualizer
    ) {
        $container = $contextualizer->getContainer();
        if ($container->hasParameter("ibexa.site_access.config.$currentScope.languages")) {
            $languages = $container->getParameter("ibexa.site_access.config.$currentScope.languages");
            $mainLanguage = array_shift($languages);
            if ($mainLanguage) {
                $this->siteAccessesByLanguages[$mainLanguage][] = $currentScope;
            }
        }
    }

    public function postMap(
        array $config,
        ContextualizerInterface $contextualizer
    ): void {
        $contextualizer->getContainer()->setParameter(
            'ibexa.site_access.by_language',
            $this->siteAccessesByLanguages
        );
    }
}
