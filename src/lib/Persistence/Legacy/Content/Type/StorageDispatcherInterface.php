<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\Persistence\Legacy\Content\Type;

use Ibexa\Contracts\Core\Persistence\Content\Type\FieldDefinition;

interface StorageDispatcherInterface
{
    public function publishFieldConstraintsData(FieldDefinition $fieldDefinition): void;

    public function storeFieldConstraintsData(
        FieldDefinition $fieldDefinition,
        int $status
    ): void;

    public function loadFieldConstraintsData(
        FieldDefinition $fieldDefinition,
        int $status
    ): void;

    public function deleteFieldConstraintsData(
        string $fieldTypeIdentifier,
        int $fieldDefinitionId,
        int $status
    ): void;
}
