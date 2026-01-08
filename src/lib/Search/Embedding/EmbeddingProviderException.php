<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\Search\Embedding;

use Ibexa\Contracts\Core\Search\Embedding\EmbeddingProviderExceptionInterface;
use RuntimeException;
use Throwable;

final class EmbeddingProviderException extends RuntimeException implements EmbeddingProviderExceptionInterface
{
    public static function fromThrowable(Throwable $previous, string $message): self
    {
        return new self($message, 0, $previous);
    }
}
