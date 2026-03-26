<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\Core\FieldType;

use Ibexa\Contracts\Core\Persistence\Content\Field;
use Ibexa\Contracts\Core\Persistence\Content\VersionInfo;

/**
 * Interface for external storages that support creating lightweight references
 * to another version's data instead of copying it.
 */
interface ReferenceAwareExternalStorage
{
    public const REFERENCE_LANGUAGE_CODE = 'reference-language-code';

    /**
     * Creates a reference to the original field's external data instead of copying it.
     *
     * Called for fields in languages not being edited during draft creation.
     * The implementation should store a lightweight pointer to $originalField's
     * external data (identified by `$originalField->versionNo`) and resolve it
     * in {@see FieldStorage::getFieldData()}.
     */
    public function referenceLegacyField(
        VersionInfo $versionInfo,
        Field $field,
        Field $originalField
    ): ?bool;
}
