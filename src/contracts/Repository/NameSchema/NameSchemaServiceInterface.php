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
    public function resolveUrlAliasSchema(Content $content, ContentType $contentType = null): array;

    /**
     * @param array<string, array<string, string>> $fieldMap
     * @param array<string> $languageCodes
     *
     * @return array
     */
    public function resolveNameSchema(
        Content $content,
        array $fieldMap = [],
        array $languageCodes = [],
        ContentType $contentType = null
    ): array;

    /**
     * Returns the real name for a content name pattern.
     *
     * @param array<string, array<string, string>> $fieldMap
     * @param array<string> $languageCodes
     *
     * @return array<string>
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException
     */
    public function resolve(string $nameSchema, ContentType $contentType, array $fieldMap, array $languageCodes): array;
}
