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
    public function getModels(): array
    {
        $models = $this->configResolver->getParameter('embedding_models');

        if (!is_array($models)) {
            throw new InvalidArgumentException(
                sprintf(
                    'Config parameter "embedding_models" must be an array, %s given.',
                    get_debug_type($models)
                )
            );
        }

        return $models;
    }

    /**
     * @return string[]
     */
    public function getModelIdentifiers(): array
    {
        return array_keys($this->getModels());
    }

    /**
     * @return array{name: string, dimensions: int, field_suffix: string, embedding_provider: string}
     */
    public function getModel(string $identifier): array
    {
        $models = $this->getModels();

        if (!isset($models[$identifier])) {
            throw new InvalidArgumentException(
                sprintf('Embedding model "%s" is not configured.', $identifier)
            );
        }

        return $models[$identifier];
    }

    public function getDefaultModelIdentifier(): string
    {
        $identifier = $this->configResolver->getParameter('default_embedding_model');

        if (!is_string($identifier)) {
            throw new InvalidArgumentException(
                sprintf(
                    'Config parameter "default_embedding_model" must be a string, %s given.',
                    get_debug_type($identifier)
                )
            );
        }

        return $identifier;
    }

    /**
     * @return array{name: string, dimensions: int, field_suffix: string, embedding_provider: string}
     */
    public function getDefaultModel(): array
    {
        return $this->getModel(
            $this->getDefaultModelIdentifier()
        );
    }

    public function getDefaultProvider(): string
    {
        $provider = $this->getDefaultModel()['embedding_provider'];

        if (!is_string($provider)) {
            throw new InvalidArgumentException(
                sprintf(
                    'Default embedding model must define a string "embedding_provider", %s given.',
                    get_debug_type($provider)
                )
            );
        }

        return $provider;
    }

    public function getDefaultModelFieldSuffix(): string
    {
        $fieldSuffix = $this->getDefaultModel()['field_suffix'];

        if (!is_string($fieldSuffix)) {
            throw new InvalidArgumentException(
                sprintf(
                    'Default embedding model must define a string "field_suffix", %s given.',
                    get_debug_type($fieldSuffix)
                )
            );
        }

        return $fieldSuffix;
    }
}
