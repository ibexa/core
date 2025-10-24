<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Bundle\Core\Command\Indexer;

use Ibexa\Core\Search\Indexer\ContentIdBatchList;
use Symfony\Component\Console\Input\InputInterface;

/**
 * @internal
 */
interface ContentIdListGeneratorStrategyInterface
{
    public function shouldPurgeIndex(InputInterface $input): bool;

    public function getBatchList(
        InputInterface $input,
        int $batchSize
    ): ContentIdBatchList;
}
