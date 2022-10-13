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
 * Configuration parser for user identifier configuration.
 *
 * Example configuration:
 * ```yaml
 * ibexa:
 *   system:
 *      default: # configuration per siteaccess or siteaccess group
 *          user_content_type_identifier: ['user', 'my_custom_user_identifier']
 * ```
 */
final class UserContentTypeIdentifier extends AbstractParser
{
    /**
     * Adds semantic configuration definition.
     *
     * @param \Symfony\Component\Config\Definition\Builder\NodeBuilder $nodeBuilder Node just under ezpublish.system.<siteaccess>
     */
    public function addSemanticConfig(NodeBuilder $nodeBuilder)
    {
        $nodeBuilder
            ->arrayNode('user_content_type_identifier')
                ->info('User Content Type identifier configuration.')
                ->example(['user', 'my_custom_user_identifier'])
                ->requiresAtLeastOneElement()
                ->prototype('scalar')->end()
            ->end();
    }

    public function mapConfig(array &$scopeSettings, $currentScope, ContextualizerInterface $contextualizer): void
    {
        if (empty($scopeSettings['user_content_type_identifier'])) {
            return;
        }

        $contextualizer->setContextualParameter(
            'user_content_type_identifier',
            $currentScope,
            $scopeSettings['user_content_type_identifier']
        );
    }
}

class_alias(UserContentTypeIdentifier::class, 'EzSystems\EzPlatformAdminUiBundle\DependencyInjection\Configuration\Parser\UserIdentifier');
class_alias(UserContentTypeIdentifier::class, 'Ibexa\Bundle\AdminUi\DependencyInjection\Configuration\Parser\UserIdentifier');
