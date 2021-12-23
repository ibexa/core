<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Integration\Core\FieldType\FieldConstraintsStorage\Stub;

use Ibexa\Contracts\Core\FieldType\FieldConstraintsStorage;
use Ibexa\Contracts\Core\Persistence\Content\FieldTypeConstraints;

/**
 * Dummy in-memory implementation of \Ibexa\Contracts\Core\FieldType\FieldConstraintsStorage.
 */
final class ExampleFieldConstraintsStorage implements FieldConstraintsStorage
{
    /** @var \Ibexa\Contracts\Core\Persistence\Content\FieldTypeConstraints[] */
    private array $fieldConstraints;

    /**
     * @param \Ibexa\Contracts\Core\Persistence\Content\FieldTypeConstraints[]
     */
    public function __construct(array $fieldConstraints = [])
    {
        $this->fieldConstraints = $fieldConstraints;
    }

    public function storeFieldConstraintsData(
        int $fieldDefinitionId,
        FieldTypeConstraints $fieldTypeConstraints
    ): void {
        $this->fieldConstraints[$fieldDefinitionId] = $fieldTypeConstraints;
    }

    public function hasFieldConstraintsData(
        int $fieldDefinitionId
    ): bool {
        return isset($this->fieldConstraints[$fieldDefinitionId]);
    }

    public function getFieldConstraintsData(
        int $fieldDefinitionId
    ): FieldTypeConstraints {
        return $this->fieldConstraints[$fieldDefinitionId];
    }

    public function getFieldConstraintsDataIfAvailable(
        int $fieldDefinitionId
    ): ?FieldTypeConstraints {
        return $this->fieldConstraints[$fieldDefinitionId] ?? null;
    }

    public function deleteFieldConstraintsData(int $fieldDefinitionId): void
    {
        unset($this->fieldConstraints[$fieldDefinitionId]);
    }
}
