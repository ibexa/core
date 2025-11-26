<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\Search\Embedding;

use Ibexa\Contracts\Core\Pool\Pool;
use Ibexa\Contracts\Core\Pool\PoolInterface;
use Ibexa\Contracts\Core\Search\Embedding\EmbeddingProviderInterface;
use Ibexa\Contracts\Core\Search\Embedding\EmbeddingProviderRegistryInterface;

final class EmbeddingProviderRegistry implements EmbeddingProviderRegistryInterface
{
    /** @var \Ibexa\Contracts\Core\Pool\PoolInterface<\Ibexa\Contracts\Core\Search\Embedding\EmbeddingProviderInterface> */
    private PoolInterface $pool;

    /**
     * @param iterable<\Ibexa\Contracts\Core\Search\Embedding\EmbeddingProviderInterface> $embeddingProviders
     */
    public function __construct(iterable $embeddingProviders = [])
    {
        $this->pool = new Pool(EmbeddingProviderInterface::class, $embeddingProviders);
        $this->pool->setExceptionArgumentName('embedding_provider');
        $this->pool->setExceptionMessageTemplate('Could not find %s for \'%s\' embedding provider.');
    }

    public function getEmbeddingProviders(): iterable
    {
        return $this->pool->getEntries();
    }

    public function hasEmbeddingProvider(string $identifier): bool
    {
        return $this->pool->has($identifier);
    }

    public function getEmbeddingProvider(string $identifier): EmbeddingProviderInterface
    {
        return $this->pool->get($identifier);
    }
}
