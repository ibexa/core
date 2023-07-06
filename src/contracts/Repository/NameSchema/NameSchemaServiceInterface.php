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

    public function resolveNameSchema(
        Content $content,
        array $fieldMap = [],
        array $languageCodes = [],
        ContentType $contentType = null
    ): array;

    public function resolve(string $nameSchema, ContentType $contentType, array $fieldMap, array $languageCodes): array;
}
