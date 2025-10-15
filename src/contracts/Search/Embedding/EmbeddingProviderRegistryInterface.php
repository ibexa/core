<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\Core\Search\Embedding;

interface EmbeddingProviderRegistryInterface
{
    /**
     * @return \Ibexa\Contracts\Core\Search\Embedding\EmbeddingProviderInterface[]
     */
    public function getEmbeddingProviders(): iterable;

    public function hasEmbeddingProvider(string $identifier): bool;

    public function getEmbeddingProvider(string $identifier): EmbeddingProviderInterface;
}
