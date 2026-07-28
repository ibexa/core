<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\Persistence\Doctrine;

enum DatabasePlatformName: string
{
    case Mysql = 'mysql';
    case Postgresql = 'postgresql';
    case Sqlite = 'sqlite';
}
