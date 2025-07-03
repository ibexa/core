<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\Core\Search\FieldType;

use Ibexa\Contracts\Core\Search\Embedding\EmbeddingConfigurationInterface;

final class EmbeddingFieldFactory
{
    private EmbeddingConfigurationInterface $config;

    public function __construct(EmbeddingConfigurationInterface $config)
    {
        $this->config = $config;
    }

    public function create(?string $type = null): EmbeddingField
    {
        if ($type !== null) {
            return EmbeddingField::create($type);
        }

        $suffix = $this->config->getDefaultEmbeddingModelFieldSuffix();

        return EmbeddingField::create('ibexa_dense_vector_' . $suffix);
    }
}
