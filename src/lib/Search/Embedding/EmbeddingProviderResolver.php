<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\Search\Embedding;

use Ibexa\Contracts\Core\Search\Embedding\EmbeddingConfigurationInterface;
use Ibexa\Contracts\Core\Search\Embedding\EmbeddingProviderInterface;
use Ibexa\Contracts\Core\Search\Embedding\EmbeddingProviderRegistryInterface;
use Ibexa\Contracts\Core\Search\Embedding\EmbeddingProviderResolverInterface;
use RuntimeException;

final class EmbeddingProviderResolver implements EmbeddingProviderResolverInterface
{
    private EmbeddingConfigurationInterface $embeddingConfiguration;

    private EmbeddingProviderRegistryInterface $registry;

    public function __construct(
        EmbeddingConfigurationInterface $embeddingConfiguration,
        EmbeddingProviderRegistryInterface $registry
    ) {
        $this->embeddingConfiguration = $embeddingConfiguration;
        $this->registry = $registry;
    }

    public function resolve(): EmbeddingProviderInterface
    {
        $defaultEmbeddingProvider = $this->embeddingConfiguration->getDefaultEmbeddingProvider();

        if (!$this->registry->hasEmbeddingProvider($defaultEmbeddingProvider)) {
            throw new RuntimeException(
                sprintf('No embedding provider registered for identifier "%s".', $defaultEmbeddingProvider)
            );
        }

        return $this->registry->getEmbeddingProvider($defaultEmbeddingProvider);
    }
}
