<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\Core\FieldType;

/**
 * Base class for FieldType external storage gateways.
 */
abstract class StorageGateway implements StorageGatewayInterface
{
    /**
     * Get sequence name bound to database table and column.
     */
    protected function getSequenceName(string $table, string $column): string
    {
        return sprintf('%s_%s_seq', $table, $column);
    }
}
