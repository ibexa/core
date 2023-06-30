<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
namespace Ibexa\Core\Repository\Helper;

use Ibexa\Contracts\Core\Repository\Values\Content\Content;
use Ibexa\Contracts\Core\Repository\Values\ContentType\ContentType;
use Ibexa\Core\Repository\NameSchema\NameSchemaService as NativeNameSchemaService;

/**
 * @deprecated inject \Ibexa\Contracts\Core\Repository\NameSchema\NameSchemaServiceInterface instead.
 * @see \Ibexa\Contracts\Core\Repository\NameSchema\NameSchemaServiceInterface
 */
class NameSchemaService extends NativeNameSchemaService
{
    public function resolveUrlAliasSchema(Content $content, ContentType $contentType = null): array
    {
        $contentType = $contentType ?? $content->getContentType();

        return $this->resolve(
            empty($contentType->urlAliasSchema) ? $contentType->nameSchema : $contentType->urlAliasSchema,
            $contentType,
            $content->fields,
            $content->versionInfo->languageCodes
        );
    }
}

class_alias(NameSchemaService::class, 'eZ\Publish\Core\Repository\Helper\NameSchemaService');
