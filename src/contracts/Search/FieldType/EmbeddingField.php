<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\Core\Search\FieldType;

use Ibexa\Contracts\Core\Search\FieldType;

final class EmbeddingField extends FieldType
{
    private function __construct(string $type)
    {
        parent::__construct(['type' => $type]);
    }

    /**
     * @param string $type Has to be handled by configured search engine (ibexa_dense_vector_ada002).
     */
    public static function create(string $type): self
    {
        return new self($type);
    }
}
