<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\Persistence\Legacy\Content\UrlWildcard;

use Ibexa\Contracts\Core\Persistence\Content\UrlWildcard;
use Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException;
use Ibexa\Contracts\Core\Repository\Exceptions\NotImplementedException;
use Ibexa\Contracts\Core\Repository\Values\Content\URLWildcard\Query\Criterion;

/**
 * UrlWildcard Gateway.
 *
 * @internal For internal use by Persistence Handlers.
 */
abstract class Gateway
{
    public const URL_WILDCARD_TABLE = 'ibexa_url_wildcard';
    public const URL_WILDCARD_SEQ = 'ibexa_url_wildcard_id_seq';

    /**
     * Insert the given UrlWildcard.
     */
    abstract public function insertUrlWildcard(UrlWildcard $urlWildcard): int;

    /**
     * Update the given UrlWildcard.
     */
    abstract public function updateUrlWildcard(
        int $id,
        string $sourceUrl,
        string $destinationUrl,
        bool $forward
    ): void;

    /**
     * Delete the UrlWildcard with given $id.
     */
    abstract public function deleteUrlWildcard(int $id): void;

    /**
     * Load an array with data about UrlWildcard with $id.
     */
    abstract public function loadUrlWildcardData(int $id): array;

    /**
     * Load an array with data about UrlWildcards (paged).
     */
    abstract public function loadUrlWildcardsData(
        int $offset = 0,
        int $limit = -1
    ): array;

    /**
     * Selects URLWildcards matching specified criteria.
     *
     * @return array{
     *     "rows": mixed,
     *     "count": int|null,
     * }
     *
     * @throws InvalidArgumentException
     * @throws NotImplementedException if Criterion is not applicable to its target
     */
    abstract public function find(
        Criterion $criterion,
        int $offset,
        int $limit,
        array $sortClauses = [],
        bool $doCount = true
    ): array;

    /**
     * Load the UrlWildcard by source url $sourceUrl.
     */
    abstract public function loadUrlWildcardBySourceUrl(string $sourceUrl): array;

    abstract public function countAll(): int;
}
