<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Bundle\Core\DependencyInjection\Configuration;

use Ibexa\Bundle\Core\DependencyInjection\Configuration\SiteAccessAware\Configuration as SiteAccessConfiguration;
use Symfony\Component\Config\Definition\Builder\NodeBuilder;

/**
 * @phpstan-import-type TRootNode from SiteAccessConfiguration
 */
interface RepositoryConfigParserInterface
{
    /**
     * @phpstan-param NodeBuilder<TRootNode> $nodeBuilder
     */
    public function addSemanticConfig(NodeBuilder $nodeBuilder): void;
}
