<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\Persistence\Legacy\Content\Type;

use Ibexa\Contracts\Core\Exception\InvalidArgumentException;
use Ibexa\Contracts\Core\FieldType\FieldConstraintsStorage;

final class StorageRegistry implements StorageRegistryInterface
{
    /** @var iterable<string,\Ibexa\Contracts\Core\FieldType\FieldConstraintsStorage> */
    private iterable $storages;

    public function __construct(iterable $storages)
    {
        $this->storages = $storages;
    }

    public function hasStorage(string $fieldTypeName): bool
    {
        return $this->findStorage($fieldTypeName) !== null;
    }

    /**
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException
     */
    public function getStorage(string $fieldTypeName): FieldConstraintsStorage
    {
        $storage = $this->findStorage($fieldTypeName);
        if ($storage === null) {
            throw new InvalidArgumentException(
                '$typeName',
                sprintf('Undefined %s for "%s" field type', FieldConstraintsStorage::class, $fieldTypeName)
            );
        }

        return $storage;
    }

    private function findStorage(string $needle): ?FieldConstraintsStorage
    {
        foreach ($this->storages as $fieldTypeName => $storage) {
            if ($fieldTypeName === $needle) {
                return $storage;
            }
        }

        return null;
    }
}
