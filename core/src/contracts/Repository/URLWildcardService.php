<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\Core\Repository;

use Ibexa\Contracts\Core\Repository\Values\Content\URLWildcard;
use Ibexa\Contracts\Core\Repository\Values\Content\URLWildcard\SearchResult;
use Ibexa\Contracts\Core\Repository\Values\Content\URLWildcard\URLWildcardQuery;
use Ibexa\Contracts\Core\Repository\Values\Content\URLWildcardTranslationResult;
use Ibexa\Contracts\Core\Repository\Values\Content\URLWildcardUpdateStruct;

/**
 * URLAlias service.
 *
 * @example Examples/urlalias.php
 */
interface URLWildcardService
{
    /**
     * Creates a new url wildcard.
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException if the $sourceUrl pattern already exists
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\UnauthorizedException if the user is not allowed to create url wildcards
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\ContentValidationException if the number of "*" patterns in $sourceUrl and
     *          the number of {\d} placeholders in $destinationUrl doesn't match or
     *          if the placeholders aren't a valid number sequence({1}/{2}/{3}), starting with 1.
     */
    public function create(string $sourceUrl, string $destinationUrl, bool $forward = false): URLWildcard;

    /**
     * Update an url wildcard.
     *
     * @param \Ibexa\Contracts\Core\Repository\Values\Content\URLWildcard $urlWildcard
     * @param \Ibexa\Contracts\Core\Repository\Values\Content\URLWildcardUpdateStruct $updateStruct
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\UnauthorizedException if the user is not allowed to update url wildcards
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\ContentValidationException if the number of "*" patterns in $sourceUrl and
     *          the number of {\d} placeholders in $destinationUrl doesn't match or
     *          if the placeholders aren't a valid number sequence({1}/{2}/{3}), starting with 1.
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\BadStateException
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException if the $sourceUrl pattern already exists
     */
    public function update(
        URLWildcard $urlWildcard,
        URLWildcardUpdateStruct $updateStruct
    ): void;

    /**
     * Removes an url wildcard.
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\UnauthorizedException if the user is not allowed to remove url wildcards
     *
     * @param \Ibexa\Contracts\Core\Repository\Values\Content\URLWildcard $urlWildcard the url wildcard to remove
     */
    public function remove(URLWildcard $urlWildcard): void;

    /**
     * Loads a url wild card.
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException if the url wild card was not found
     */
    public function load(int $id): URLWildcard;

    /**
     * Loads all url wild card (paged).
     *
     * @param int $offset
     * @param int $limit
     *
     * @return \Ibexa\Contracts\Core\Repository\Values\Content\URLWildcard[]
     */
    public function loadAll(int $offset = 0, int $limit = -1): iterable;

    /**
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\UnauthorizedException
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException
     */
    public function findUrlWildcards(URLWildcardQuery $query): SearchResult;

    /**
     * Translates an url to an existing uri resource based on the
     * source/destination patterns of the url wildcard.
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException if the url could not be translated
     *
     * @param string $url
     *
     * @return \Ibexa\Contracts\Core\Repository\Values\Content\URLWildcardTranslationResult
     */
    public function translate(string $url): URLWildcardTranslationResult;

    /**
     * Counts URL Wildcards.
     */
    public function countAll(): int;
}
