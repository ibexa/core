<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\Core\FieldType;

use Ibexa\Contracts\Core\Persistence\Content\FieldTypeConstraints;

interface FieldConstraintsStorage
{
    public function storeFieldConstraintsData(
        int $fieldDefinitionId,
        FieldTypeConstraints $fieldTypeConstraints
    );

    public function getFieldConstraintsData(
        int $fieldDefinitionId
    ): FieldTypeConstraints;

    public function deleteFieldConstraintsData(
        int $fieldDefinitionId
    ): void;
}
