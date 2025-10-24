<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\Core\Repository\Values\Content\Search;

final class SpellcheckResult
{
    /**
     * Query with applied corrections.
     */
    private ?string $query;

    /**
     * Flag indicating that corrections have been applied to input query.
     */
    private bool $incorrect;

    public function __construct(
        ?string $query,
        bool $incorrect = true
    ) {
        $this->query = $query;
        $this->incorrect = $incorrect;
    }

    public function getQuery(): ?string
    {
        return $this->query;
    }

    public function isIncorrect(): bool
    {
        return $this->incorrect;
    }
}
