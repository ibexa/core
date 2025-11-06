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
    public function getModels(): array;

    /**
     * @return string[]
     */
    public function getModelIdentifiers(): array;

    /**
     * @return array{name: string, dimensions: int, field_suffix: string, embedding_provider: string}
     */
    public function getModel(string $identifier): array;

    public function getDefaultModelIdentifier(): string;

    /**
     * @return array{name: string, dimensions: int, field_suffix: string, embedding_provider: string}
     */
    public function getDefaultModel(): array;

    public function getDefaultProvider(): string;

    public function getDefaultModelFieldSuffix(): string;
}
