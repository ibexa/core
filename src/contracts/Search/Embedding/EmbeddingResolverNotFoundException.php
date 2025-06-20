<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
namespace Ibexa\Contracts\Core\Search\Embedding;

use RuntimeException;

final class EmbeddingResolverNotFoundException extends RuntimeException
{
    public function __construct(
        string $embeddingProvider
    ) {
        $message = sprintf('No embedding provider registered for identifier "%s".', $embeddingProvider);

        parent::__construct($message);
    }
}
