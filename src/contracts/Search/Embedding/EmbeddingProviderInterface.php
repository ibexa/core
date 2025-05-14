<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\Core\Search\Embedding;

interface EmbeddingProviderInterface
{
    /**
     * @return float[]
     */
    public function getEmbedding(string $content, ?string $model = null): array;
}
