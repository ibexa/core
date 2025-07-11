<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\FieldType;

final readonly class FieldTypeAliasResolver implements FieldTypeAliasResolverInterface
{
    public function __construct(
        private FieldTypeAliasRegistry $fieldTypeAliasRegistry,
    ) {
    }

    public function resolveIdentifier(string $alias): string
    {
        if ($this->fieldTypeAliasRegistry->hasAlias($alias)) {
            return $this->fieldTypeAliasRegistry->getNewAlias($alias);
        }

        return $alias;
    }
}
