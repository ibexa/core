<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\Core\Search\Embedding;

/**
 * Provides easy access to embedding-related configuration.
 */
interface EmbeddingConfigurationInterface
{
    /**
     * @return array<string, array{name: string, dimensions: int, field_suffix: string, embedding_provider: string}>
     */
    public function getEmbeddingModels(): array;

    /**
     * @return string[]
     */
    public function getEmbeddingModelIdentifiers(): array;

    /**
     * @return array{name: string, dimensions: int, field_suffix: string, embedding_provider: string}
     */
    public function getEmbeddingModel(string $identifier): array;

    public function getDefaultEmbeddingModelIdentifier(): string;

    /**
     * @return array{name: string, dimensions: int, field_suffix: string, 'embedding_provider': string}
     */
    public function getDefaultEmbeddingModel(): array;

    public function getDefaultEmbeddingProvider(): string;

    public function getDefaultEmbeddingModelFieldSuffix(): string;
}
