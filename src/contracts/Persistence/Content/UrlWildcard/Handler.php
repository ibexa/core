<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Contracts\Core\Persistence\Content\UrlWildcard;

use Ibexa\Contracts\Core\Persistence\Content\UrlWildcard;
use Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException;
use Ibexa\Contracts\Core\Repository\Exceptions\UnauthorizedException;
use Ibexa\Contracts\Core\Repository\Values\Content\URLWildcard\URLWildcardQuery;

/**
 * The UrlWildcard Handler interface provides nice urls with wildcards management.
 *
 * Its methods operate on a representation of the url alias data structure held
 * inside a storage engine.
 */
interface Handler
{
    /**
     * Creates a new url wildcard.
     *
     * @param string $sourceUrl
     * @param string $destinationUrl
     * @param bool $forward
     *
     * @return UrlWildcard
     */
    public function create(
        $sourceUrl,
        $destinationUrl,
        $forward = false
    );

    public function update(
        int $id,
        string $sourceUrl,
        string $destinationUrl,
        bool $forward
    ): UrlWildcard;

    /**
     * removes an url wildcard.
     *
     * @throws NotFoundException if the url wild card was not found
     *
     * @param mixed $id
     */
    public function remove($id);

    /**
     * Loads a url wild card.
     *
     * @throws NotFoundException if the url wild card was not found
     *
     * @param mixed $id
     *
     * @return UrlWildcard
     */
    public function load($id);

    /**
     * Loads all url wild card (paged).
     *
     * @param int $offset
     * @param int $limit
     *
     * @return UrlWildcard[]
     */
    public function loadAll(
        $offset = 0,
        $limit = -1
    );

    /**
     * Find URLWildcards.
     *
     * @throws UnauthorizedException
     */
    public function find(URLWildcardQuery $query): array;

    /**
     * Performs lookup for given (source) URL.
     *
     * @throws NotFoundException if the url wild card was not found
     *
     * @param string $sourceUrl
     *
     * @return UrlWildcard
     */
    public function translate(string $sourceUrl): UrlWildcard;

    /**
     * Checks whether UrlWildcard with given source url exits.
     *
     * @param string $sourceUrl
     *
     * @return bool
     */
    public function exactSourceUrlExists(string $sourceUrl): bool;

    /**
     * Counts URL Wildcards.
     */
    public function countAll(): int;
}
