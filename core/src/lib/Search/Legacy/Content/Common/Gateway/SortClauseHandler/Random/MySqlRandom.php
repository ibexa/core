<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\Search\Legacy\Content\Common\Gateway\SortClauseHandler\Random;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Platforms\MySQLPlatform;
use Ibexa\Core\Search\Legacy\Content\Common\Gateway\SortClauseHandler\AbstractRandom;

class MySqlRandom extends AbstractRandom
{
    public function supportsPlatform(AbstractPlatform $platform): bool
    {
        return $platform instanceof MySQLPlatform;
    }

    public function getRandomFunctionName(?int $seed): string
    {
        return 'RAND(' . $seed . ')';
    }
}
