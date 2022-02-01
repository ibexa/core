<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\Persistence\Legacy\Content\Type;

use Ibexa\Contracts\Core\Persistence\Content\Type\FieldDefinition;

final class StorageDispatcher implements StorageDispatcherInterface
{
    private StorageRegistryInterface $registry;

    public function __construct(StorageRegistryInterface $registry)
    {
        $this->registry = $registry;
    }

    public function publishFieldConstraintsData(FieldDefinition $fieldDefinition): void
    {
        if ($this->registry->hasStorage($fieldDefinition->fieldType)) {
            $storage = $this->registry->getStorage($fieldDefinition->fieldType);
            $storage->publishFieldConstraintsData($fieldDefinition->id);
        }
    }

    public function storeFieldConstraintsData(FieldDefinition $fieldDefinition, int $status): void
    {
        if ($this->registry->hasStorage($fieldDefinition->fieldType)) {
            $storage = $this->registry->getStorage($fieldDefinition->fieldType);
            $storage->storeFieldConstraintsData($fieldDefinition->id, $status, $fieldDefinition->fieldTypeConstraints);
        }
    }

    public function loadFieldConstraintsData(FieldDefinition $fieldDefinition, int $status): void
    {
        if ($this->registry->hasStorage($fieldDefinition->fieldType)) {
            $storage = $this->registry->getStorage($fieldDefinition->fieldType);

            $fieldDefinition->fieldTypeConstraints = $storage->getFieldConstraintsData($fieldDefinition->id, $status);
        }
    }

    public function deleteFieldConstraintsData(string $fieldTypeIdentifier, int $fieldDefinitionId, int $status): void
    {
        if ($this->registry->hasStorage($fieldTypeIdentifier)) {
            $storage = $this->registry->getStorage($fieldTypeIdentifier);
            $storage->deleteFieldConstraintsData($fieldDefinitionId, $status);
        }
    }
}
