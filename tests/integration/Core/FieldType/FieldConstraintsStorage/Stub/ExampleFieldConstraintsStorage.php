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
    /** @var FieldTypeConstraints[] */
    private array $fieldConstraints;

    /** @var int[] */
    private array $published = [];

    /**
     * @param FieldTypeConstraints[]
     */
    public function __construct(array $fieldConstraints = [])
    {
        $this->fieldConstraints = $fieldConstraints;
        $this->published = [];
    }

    public function publishFieldConstraintsData(int $fieldDefinitionId): void
    {
        $this->published[] = $fieldDefinitionId;
    }

    public function storeFieldConstraintsData(
        int $fieldDefinitionId,
        int $status,
        FieldTypeConstraints $fieldTypeConstraints
    ): void {
        $this->fieldConstraints[$fieldDefinitionId] = $fieldTypeConstraints;
    }

    public function hasFieldConstraintsData(
        int $fieldDefinitionId
    ): bool {
        return isset($this->fieldConstraints[$fieldDefinitionId]);
    }

    public function isPublished(int $fieldDefinitionId): bool
    {
        return in_array($fieldDefinitionId, $this->published, true);
    }

    public function getFieldConstraintsData(
        int $fieldDefinitionId,
        int $status
    ): FieldTypeConstraints {
        return $this->fieldConstraints[$fieldDefinitionId];
    }

    public function getFieldConstraintsDataIfAvailable(
        int $fieldDefinitionId
    ): ?FieldTypeConstraints {
        return $this->fieldConstraints[$fieldDefinitionId] ?? null;
    }

    public function deleteFieldConstraintsData(
        int $fieldDefinitionId,
        int $status
    ): void {
        unset($this->fieldConstraints[$fieldDefinitionId]);
    }
}
