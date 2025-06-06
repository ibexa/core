<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\Search\Embedding;

use Ibexa\Contracts\Core\Search\Embedding\EmbeddingConfigurationInterface;
use Ibexa\Contracts\Core\SiteAccess\ConfigResolverInterface;
use InvalidArgumentException;

final class EmbeddingConfiguration implements EmbeddingConfigurationInterface
{
    private ConfigResolverInterface $configResolver;

    public function __construct(
        ConfigResolverInterface $configResolver
    ) {
        $this->configResolver = $configResolver;
    }

    /**
     * @return array<string, array{name: string, dimensions: int, field_suffix: string, embedding_provider: string}>
     */
    public function getEmbeddingModels(): array
    {
        return (array)$this->configResolver->getParameter('embedding_models');
    }

    /**
     * @return string[]
     */
    public function getEmbeddingModelIdentifiers(): array
    {
        return array_keys($this->getEmbeddingModels());
    }

    /**
     * @return array{name: string, dimensions: int, field_suffix: string, embedding_provider: string}
     */
    public function getEmbeddingModel(string $identifier): array
    {
        $models = $this->getEmbeddingModels();

        if (!isset($models[$identifier])) {
            throw new InvalidArgumentException(
                sprintf('Embedding model "%s" is not configured.', $identifier)
            );
        }

        return $models[$identifier];
    }

    public function getDefaultEmbeddingModelIdentifier(): string
    {
        return (string)$this->configResolver->getParameter('default_embedding_model');
    }

    /**
     * @return array{name: string, dimensions: int, field_suffix: string, 'embedding_provider': string}
     */
    public function getDefaultEmbeddingModel(): array
    {
        return $this->getEmbeddingModel(
            $this->getDefaultEmbeddingModelIdentifier()
        );
    }

    public function getDefaultEmbeddingProvider(): string
    {
        return (string)$this->getDefaultEmbeddingModel()['embedding_provider'];
    }

    public function getDefaultEmbeddingModelFieldSuffix(): string
    {
        return (string)$this->getDefaultEmbeddingModel()['field_suffix'];
    }
}
