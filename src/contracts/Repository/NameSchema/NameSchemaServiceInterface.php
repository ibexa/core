<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\Core\Repository\NameSchema;

use Ibexa\Contracts\Core\Repository\Values\Content\Content;
use Ibexa\Contracts\Core\Repository\Values\ContentType\ContentType;

/**
 * @internal Meant to be used by 1st party only
 *
 * @experimental
 */
interface NameSchemaServiceInterface
{
    /**
     * @return array<string, string> key value map of names for a language code
     */
    public function resolveUrlAliasSchema(
        Content $content,
        ?ContentType $contentType = null
    ): array;

    /**
     * @param array<int|string, array<string, \Ibexa\Contracts\Core\FieldType\Value>> $fieldMap
     * @param array<string> $languageCodes
     *
     * @return array<string, string>
     */
    public function resolveContentNameSchema(
        Content $content,
        array $fieldMap = [],
        array $languageCodes = [],
        ?ContentType $contentType = null
    ): array;

    /**
     * Returns the real name for a content name pattern.
     *
     * @param array<int|string, array<string, \Ibexa\Contracts\Core\FieldType\Value>>  $fieldMap
     * @param array<string> $languageCodes
     *
     * @return array<string, string>
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException
     */
    public function resolveNameSchema(
        string $nameSchema,
        ContentType $contentType,
        array $fieldMap,
        array $languageCodes
    ): array;
}
