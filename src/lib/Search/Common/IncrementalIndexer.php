<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Core\Search\Common;

/**
 * Base class for the Search Engine Indexer Service aimed to recreate Search Engine Index.
 * Each Search Engine has to extend it on its own.
 *
 * Extends indexer to allow for reindexing your install while it is in production by splitting indexing into tree tasks:
 * - Remove items in index no longer valid in database
 * - Making purge of index optional
 * - indexing by specifying id's, for purpose of supporting parallel indexing
 *
 * @api
 */
abstract class IncrementalIndexer extends Indexer
{
    /**
     * Updates search engine index based on Content id's.
     *
     * If content is:
     * - deleted (NotFoundException)
     * - not published (draft or trashed)
     * Then item is removed from index, if not it is added/updated.
     *
     * If generic unhandled exception is thrown, then item indexing is skipped and warning is logged.
     *
     * @param int[] $contentIds
     * @param bool $commit
     */
    abstract public function updateSearchIndex(array $contentIds, $commit);

    /**
     * Purges whole index, should only be done if user asked for it.
     */
    abstract public function purge();

    /**
     * Return human readable name of given search engine (and if custom indexer you can append that to).
     *
     * @return string
     */
    abstract public function getName();
}
