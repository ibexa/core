<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Contracts\Core\Search;

use Ibexa\Contracts\Core\Persistence\Content;
use Ibexa\Contracts\Core\Persistence\Content\ContentInfo;
use Ibexa\Contracts\Core\Persistence\Content\Location;
use Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException;
use Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException;
use Ibexa\Contracts\Core\Repository\SearchService;
use Ibexa\Contracts\Core\Repository\Values\Content\LocationQuery;
use Ibexa\Contracts\Core\Repository\Values\Content\Query;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\CriterionInterface;
use Ibexa\Contracts\Core\Repository\Values\Content\Search\SearchResult;

/**
 * The Search handler retrieves sets of of Content objects, based on a
 * set of criteria.
 *
 * @phpstan-import-type TSearchLanguageFilter from \Ibexa\Contracts\Core\Repository\SearchService
 */
interface Handler
{
    /**
     * Finds content objects for the given query.
     *
     * @throws InvalidArgumentException if Query criterion is not applicable to its target
     *
     * @phpstan-param TSearchLanguageFilter $languageFilter {@see SearchService::findContent}
     *
     * @phpstan-return SearchResult<ContentInfo>
     */
    public function findContent(
        Query $query,
        array $languageFilter = []
    ): SearchResult;

    /**
     * Performs a query for a single content object.
     *
     * @throws NotFoundException if the object was not found by the query or due to permissions
     * @throws InvalidArgumentException if Criterion is not applicable to its target
     * @throws InvalidArgumentException if there is more than one result matching the criteria
     *
     * @phpstan-param TSearchLanguageFilter $languageFilter {@see \Ibexa\Contracts\Core\Repository\SearchService::findSingle()}
     */
    public function findSingle(
        CriterionInterface $filter,
        array $languageFilter = []
    ): ContentInfo;

    /**
     * Finds locations for the given $query.
     *
     * @phpstan-param TSearchLanguageFilter $languageFilter {@see \Ibexa\Contracts\Core\Repository\SearchService::findSingle()}
     *
     * @phpstan-return SearchResult<Location>
     */
    public function findLocations(
        LocationQuery $query,
        array $languageFilter = []
    ): SearchResult;

    /**
     * Suggests a list of values for the given prefix.
     *
     * @param string $prefix
     * @param string[] $fieldPaths
     * @param int $limit
     * @param Criterion|null $filter
     */
    public function suggest(
        $prefix,
        $fieldPaths = [],
        $limit = 10,
        ?Criterion $filter = null
    );

    /**
     * Indexes a content object.
     *
     * @param Content $content
     */
    public function indexContent(Content $content);

    /**
     * Deletes a content object from the index.
     *
     * @param int $contentId
     * @param int|null $versionId
     */
    public function deleteContent(
        $contentId,
        $versionId = null
    );

    /**
     * Indexes a Location in the index storage.
     *
     * @param Location $location
     */
    public function indexLocation(Location $location);

    /**
     * Deletes a location from the index.
     *
     * @param mixed $locationId
     * @param mixed $contentId
     */
    public function deleteLocation(
        $locationId,
        $contentId
    );

    /**
     * Purges all contents from the index.
     */
    public function purgeIndex();
}
