<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Bundle\Core\DependencyInjection\Configuration\Parser;

use Ibexa\Bundle\Core\DependencyInjection\Configuration\AbstractParser;
use Ibexa\Bundle\Core\DependencyInjection\Configuration\SiteAccessAware\ContextualizerInterface;
use Symfony\Component\Config\Definition\Builder\NodeBuilder;

/**
 * Configuration parser handling all basic configuration (aka "common").
 */
class Common extends AbstractParser
{
    /**
     * Adds semantic configuration definition.
     *
     * @param \Symfony\Component\Config\Definition\Builder\NodeBuilder $nodeBuilder Node just under ibexa.system.<siteaccess>
     */
    public function addSemanticConfig(NodeBuilder $nodeBuilder): void
    {
        $nodeBuilder
            ->scalarNode('repository')->info('The repository to use. Choose among ibexa.repositories.')->end()
            ->scalarNode('cache_service_name')
                ->example('cache.app')
                ->info('The cache pool service name to use for a siteaccess / siteaccess-group, *must* be present.')
            ->end()
            ->scalarNode('var_dir')
                ->cannotBeEmpty()
                ->example('var/ibexa_demo_site')
                ->info('The directory relative to web/ where files are stored. Default value is "var"')
            ->end()
            ->arrayNode('api_keys')
                ->info('Collection of API keys')
                ->scalarPrototype()->end()
            ->end()
            ->scalarNode('storage_dir')
                ->cannotBeEmpty()
                ->info("Directory where to place new files for storage, it's relative to var directory. Default value is 'storage'")
            ->end()
            ->scalarNode('binary_dir')
                ->cannotBeEmpty()
                ->info('Directory where binary files (from ibexa_binaryfile field type) are stored. Default value is "original"')
            ->end()
            ->arrayNode('session')
                ->info('Session options. Will override options defined in Symfony framework.session.*')
                ->children()
                    ->scalarNode('name')
                        ->info('The session name. If you want a session name per siteaccess, use "{siteaccess_hash}" token. Will override default session name from framework.session.name')
                        ->example('IBX_SESSION_ID{siteaccess_hash}')
                    ->end()
                    ->scalarNode('cookie_lifetime')->end()
                    ->scalarNode('cookie_path')->end()
                    ->scalarNode('cookie_domain')->end()
                    ->booleanNode('cookie_secure')->end()
                    ->booleanNode('cookie_httponly')->end()
                ->end()
            ->end()
            ->scalarNode('page_layout')
                ->info('The default layout to use')
                ->example('AppBundle::page_layout.html.twig')
            ->end()
            ->scalarNode('index_page')
                ->info('The page that the index page will show. Default value is null.')
                ->example('/Getting-Started')
            ->end()
            ->scalarNode('default_page')
                ->info('The default page to show, e.g. after user login this will be used for default redirection. If provided, will override "default_target_path" from security.yml.')
                ->example('/Getting-Started')
            ->end()
            ->arrayNode('http_cache')
                ->info('Settings related to Http cache')
                ->children()
                    ->arrayNode('purge_servers')
                        ->info('Servers to use for Http PURGE (will NOT be used if ibexa.http_cache.purge_type is "local").')
                        ->example(['http://localhost/', 'http://another.server/'])
                        ->requiresAtLeastOneElement()
                        ->prototype('scalar')->end()
                    ->end()
                ->end()
            ->end()
            ->scalarNode('anonymous_user_id')
                ->cannotBeEmpty()
                ->example('10')
                ->info('The ID of the user used for everyone who is not logged in.')
            ->end()
            ->arrayNode('user')
                ->children()
                    ->scalarNode('layout')
                        ->info('Layout template to use for user related actions. This is most likely the base pagelayout template of your site.')
                        ->example('pagelayout.html.twig')
                    ->end()
                    ->scalarNode('login_template')
                        ->info('Template to use for login form. Defaults to @IbexaCore/Security/login.html.twig')
                        ->example('login.html.twig')
                    ->end()
                ->end()
            ->end();
    }

    public function preMap(array $config, ContextualizerInterface $contextualizer)
    {
        $contextualizer->mapConfigArray('session', $config);
    }

    public function mapConfig(array &$scopeSettings, $currentScope, ContextualizerInterface $contextualizer)
    {
        if (isset($scopeSettings['repository'])) {
            $contextualizer->setContextualParameter('repository', $currentScope, $scopeSettings['repository']);
        }
        if (isset($scopeSettings['cache_service_name'])) {
            $contextualizer->setContextualParameter('cache_service_name', $currentScope, $scopeSettings['cache_service_name']);
        }
        if (isset($scopeSettings['var_dir'])) {
            $contextualizer->setContextualParameter('var_dir', $currentScope, $scopeSettings['var_dir']);
        }
        if (isset($scopeSettings['storage_dir'])) {
            $contextualizer->setContextualParameter('storage_dir', $currentScope, $scopeSettings['storage_dir']);
        }
        if (isset($scopeSettings['binary_dir'])) {
            $contextualizer->setContextualParameter('binary_dir', $currentScope, $scopeSettings['binary_dir']);
        }

        $contextualizer->setContextualParameter('api_keys', $currentScope, $scopeSettings['api_keys']);
        foreach ($scopeSettings['api_keys'] as $key => $value) {
            $contextualizer->setContextualParameter('api_keys.' . $key, $currentScope, $value);
        }

        if (isset($scopeSettings['http_cache']['purge_servers'])) {
            $contextualizer->setContextualParameter('http_cache.purge_servers', $currentScope, $scopeSettings['http_cache']['purge_servers']);
        }
        if (isset($scopeSettings['anonymous_user_id'])) {
            $contextualizer->setContextualParameter('anonymous_user_id', $currentScope, $scopeSettings['anonymous_user_id']);
        }
        if (isset($scopeSettings['user']['layout'])) {
            $contextualizer->setContextualParameter('security.base_layout', $currentScope, $scopeSettings['user']['layout']);
        }
        if (isset($scopeSettings['user']['login_template'])) {
            $contextualizer->setContextualParameter('security.login_template', $currentScope, $scopeSettings['user']['login_template']);
        }
        if (isset($scopeSettings['index_page'])) {
            $contextualizer->setContextualParameter('index_page', $currentScope, $scopeSettings['index_page']);
        }
        if (isset($scopeSettings['default_page'])) {
            $contextualizer->setContextualParameter('default_page', $currentScope, '/' . ltrim($scopeSettings['default_page'], '/'));
        }
        if (isset($scopeSettings['page_layout'])) {
            $contextualizer->setContextualParameter('page_layout', $currentScope, $scopeSettings['page_layout']);
        }
    }
}
