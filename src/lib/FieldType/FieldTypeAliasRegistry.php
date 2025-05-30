<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\FieldType;

final class FieldTypeAliasRegistry
{
    /** @var array<string, string> Map of legacy_alias => alias */
    private array $aliasMap = [];

    public function register(string $oldAlias, string $newAlias): void
    {
        $this->aliasMap[$oldAlias] = $newAlias;
    }

    public function getNewAlias(string $oldAlias): string
    {
        return $this->aliasMap[$oldAlias];
    }

    public function hasAlias(string $oldAlias): bool
    {
        return isset($this->aliasMap[$oldAlias]);
    }
}
