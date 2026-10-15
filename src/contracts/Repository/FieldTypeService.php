<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\Core\Repository;

use Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException;

/**
 * An implementation of this class provides access to FieldTypes.
 *
 * @see FieldType
 */
interface FieldTypeService
{
    /**
     * Returns a list of all field types.
     *
     * @return FieldType[]
     */
    public function getFieldTypes(): iterable;

    /**
     * Returns the FieldType registered with the given identifier.
     *
     * @param string $identifier
     *
     * @return FieldType
     *
     * @throws NotFoundException if there is no FieldType registered with $identifier
     */
    public function getFieldType(string $identifier): FieldType;

    /**
     * Returns if there is a FieldType registered under $identifier.
     *
     * @param string $identifier
     *
     * @return bool
     */
    public function hasFieldType(string $identifier): bool;
}
