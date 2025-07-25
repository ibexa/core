<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Bundle\Core\DependencyInjection\Configuration\Parser;

use Ibexa\Bundle\Core\DependencyInjection\Configuration\AbstractParser;
use Ibexa\Bundle\Core\DependencyInjection\Configuration\SiteAccessAware\ContextualizerInterface;
use Symfony\Component\Config\Definition\Builder\NodeBuilder;

/**
 * @internal
 *
 * Configuration parser for password hash configuration.
 *
 * Example configuration:
 * ```yaml
 * ibexa:
 *   system:
 *      default: # configuration per siteaccess or siteaccess group
 *          password_hash:
 *              default_type: 7
 *              update_type_on_change: false
 * ```
 */
final class PasswordHash extends AbstractParser
{
    /**
     * Adds semantic configuration definition.
     *
     * @param \Symfony\Component\Config\Definition\Builder\NodeBuilder $nodeBuilder Node just under ibexa.system.<siteaccess>
     */
    public function addSemanticConfig(NodeBuilder $nodeBuilder): void
    {
        $nodeBuilder
            ->arrayNode('password_hash')
                ->info('Password hash options')
                ->children()
                    ->integerNode('default_type')
                        ->info('Default password hash type, see the constants in Ibexa\Contracts\Core\Repository\Values\User\User.')
                        ->example('7')
                    ->end()
                    ->booleanNode('update_type_on_change')
                        ->info('Whether the password hash type should be changed when the password is changed if it differs from the default type.')
                        ->example('false')
                    ->end()
                ->end()
            ->end();
    }

    /**
     * @param array<string,mixed> $scopeSettings
     */
    public function mapConfig(array &$scopeSettings, $currentScope, ContextualizerInterface $contextualizer): void
    {
        if (!isset($scopeSettings['password_hash'])) {
            return;
        }

        $settings = $scopeSettings['password_hash'];
        if (isset($settings['default_type'])) {
            $contextualizer->setContextualParameter('password_hash.default_type', $currentScope, $settings['default_type']);
        }
        if (isset($settings['update_type_on_change'])) {
            $contextualizer->setContextualParameter('password_hash.update_type_on_change', $currentScope, $settings['update_type_on_change']);
        }
    }
}
