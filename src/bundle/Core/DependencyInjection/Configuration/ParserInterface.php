<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Bundle\Core\DependencyInjection\Configuration;

use Ibexa\Bundle\Core\DependencyInjection\Configuration\SiteAccessAware\Configuration as SiteAccessConfiguration;
use Ibexa\Bundle\Core\DependencyInjection\Configuration\SiteAccessAware\HookableConfigurationMapperInterface;
use Symfony\Component\Config\Definition\Builder\NodeBuilder;

/**
 * @phpstan-import-type TRootNode from SiteAccessConfiguration
 */
interface ParserInterface extends HookableConfigurationMapperInterface
{
    /**
     * Adds semantic configuration definition.
     *
     * @phpstan-param NodeBuilder<TRootNode> $nodeBuilder Node just under ibexa.system.<site_access>
     */
    public function addSemanticConfig(NodeBuilder $nodeBuilder);
}
