<?php

declare(strict_types=1);

namespace Ibexa\Bundle\Core\DependencyInjection\Configuration\Parser\Repository;

use Ibexa\Bundle\Core\DependencyInjection\Configuration\RepositoryConfigParserInterface;
use Symfony\Component\Config\Definition\Builder\NodeBuilder;

final class AsyncContentPublish implements RepositoryConfigParserInterface
{
    public function addSemanticConfig(NodeBuilder $nodeBuilder): void
    {
        $nodeBuilder
            ->booleanNode('async_content_publish')
                ->defaultFalse()
                ->info('Enables asynchronous content publishing.')
            ->end();
    }
}
