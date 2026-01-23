<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\Core\Repository;

use Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException;
use Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException;
use Ibexa\Contracts\Core\Repository\Exceptions\UnauthorizedException;
use Ibexa\Contracts\Core\Repository\Values\URL\SearchResult;
use Ibexa\Contracts\Core\Repository\Values\URL\URL;
use Ibexa\Contracts\Core\Repository\Values\URL\URLQuery;
use Ibexa\Contracts\Core\Repository\Values\URL\URLUpdateStruct;
use Ibexa\Contracts\Core\Repository\Values\URL\UsageSearchResult;

/**
 * URL Service.
 */
interface URLService
{
    /**
     * Instantiates a new URL update struct.
     *
     * @return URLUpdateStruct
     */
    public function createUpdateStruct(): URLUpdateStruct;

    /**
     * Find URLs.
     *
     * @throws UnauthorizedException
     *
     * @param URLQuery $query
     *
     * @return SearchResult
     */
    public function findUrls(URLQuery $query): SearchResult;

    /**
     * Find content objects using URL.
     *
     * Content is filter by user permissions.
     *
     * @param URL $url
     * @param int $offset
     * @param int $limit
     *
     * @return UsageSearchResult
     */
    public function findUsages(
        URL $url,
        int $offset = 0,
        int $limit = -1
    ): UsageSearchResult;

    /**
     * Load single URL (by ID).
     *
     * @param int $id ID of URL
     *
     * @throws NotFoundException
     * @throws UnauthorizedException
     *
     * @return URL
     */
    public function loadById(int $id): URL;

    /**
     * Load single URL (by URL).
     *
     * @param string $url URL
     *
     * @throws NotFoundException
     * @throws UnauthorizedException
     *
     * @return URL
     */
    public function loadByUrl(string $url): URL;

    /**
     * Updates URL.
     *
     * @param URL $url
     * @param URLUpdateStruct $struct
     *
     * @throws NotFoundException
     * @throws UnauthorizedException
     * @throws InvalidArgumentException if the url already exists
     *
     * @return URL
     */
    public function updateUrl(
        URL $url,
        URLUpdateStruct $struct
    ): URL;
}
