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

    public function register(string $legacyAlias, string $newAlias): void
    {
        $this->aliasMap[$legacyAlias] = $newAlias;
    }

    public function getNewAlias(string $legacyAlias): string
    {
        return $this->aliasMap[$legacyAlias];
    }

    public function hasAlias(string $legacyAlias): bool
    {
        return isset($this->aliasMap[$legacyAlias]);
    }
}
