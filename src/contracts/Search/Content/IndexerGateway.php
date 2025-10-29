<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\Core\Search\Content;

use DateTimeInterface;
use Doctrine\DBAL\Exception;
use Generator;

/**
 * @internal
 */
interface IndexerGateway
{
    /**
     * @throws Exception
     *
     * @return Generator list of Content IDs for each iteration
     */
    public function getContentSince(
        DateTimeInterface $since,
        int $iterationCount
    ): Generator;

    /**
     * @throws Exception
     */
    public function countContentSince(DateTimeInterface $since): int;

    /**
     * @throws Exception
     *
     * @return Generator list of Content IDs for each iteration
     */
    public function getContentInSubtree(
        string $locationPath,
        int $iterationCount
    ): Generator;

    /**
     * @throws Exception
     */
    public function countContentInSubtree(string $locationPath): int;

    /**
     * @throws Exception
     *
     * @return Generator list of Content IDs for each iteration
     */
    public function getAllContent(int $iterationCount): Generator;

    /**
     * @throws Exception
     */
    public function countAllContent(): int;
}
