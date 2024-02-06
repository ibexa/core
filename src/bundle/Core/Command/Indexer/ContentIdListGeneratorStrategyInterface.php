<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Bundle\Core\Command\Indexer;

use Generator;
use Symfony\Component\Console\Input\InputInterface;

/**
 * @internal
 */
interface ContentIdListGeneratorStrategyInterface
{
    /**
     * @return \Generator<int, int[]>
     */
    public function getGenerator(InputInterface $input, int $iterationCount): Generator;

    public function getCount(InputInterface $input): int;

    public function shouldPurgeIndex(): bool;
}
