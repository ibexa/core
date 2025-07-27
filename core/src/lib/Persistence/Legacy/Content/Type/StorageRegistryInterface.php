<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\Persistence\Legacy\Content\Type;

use Ibexa\Contracts\Core\FieldType\FieldConstraintsStorage;

interface StorageRegistryInterface
{
    public function hasStorage(string $fieldTypeName): bool;

    /**
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException
     */
    public function getStorage(string $fieldTypeName): FieldConstraintsStorage;
}
