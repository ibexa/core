<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\Persistence\Legacy\Token;

/**
 * @internal
 */
abstract class AbstractGateway
{
    protected function getAliasedColumns(
        string $alias,
        array $columns
    ): array {
        return array_map(
            fn (string $column) => $this->getAliasedColumn($column, $alias),
            $columns
        );
    }

    protected function getAliasedColumn(
        string $column,
        string $alias
    ): string {
        return sprintf('%s.%s', $alias, $column);
    }
}
