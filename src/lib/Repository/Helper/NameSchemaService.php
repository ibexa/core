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

        return $this->resolveNameSchema(
            empty($contentType->urlAliasSchema) ? $contentType->nameSchema : $contentType->urlAliasSchema,
            $contentType,
            $content->fields,
            $content->versionInfo->languageCodes
        );
    }

    public function resolveContentNameSchema(
        Content $content,
        array $fieldMap = [],
        array $languageCodes = [],
        ContentType $contentType = null
    ): array {
        $contentType ??= $content->getContentType();

        $languageCodes = $languageCodes ?: $content->versionInfo->languageCodes;

        return $this->resolveNameSchema(
            $contentType->nameSchema,
            $contentType,
            $this->mergeFieldMap(
                $content,
                $fieldMap,
                $languageCodes
            ),
            $languageCodes
        );
    }

    public function resolveNameSchema(
        string $nameSchema,
        ContentType $contentType,
        array $fieldMap,
        array $languageCodes
    ): array
    {
        [$filteredNameSchema, $groupLookupTable] = $this->filterNameSchema($nameSchema);
        $tokens = $this->extractTokens($filteredNameSchema);
        $schemaIdentifiers = $this->getIdentifiers($nameSchema);

        $names = [];

        foreach ($languageCodes as $languageCode) {
            // Fetch titles for language code
            $titles = $this->getFieldTitles($schemaIdentifiers, $contentType, $fieldMap, $languageCode);
            $name = $filteredNameSchema;

            // Replace tokens with real values
            foreach ($tokens as $token) {
                $string = $this->resolveToken($token, $titles, $groupLookupTable);
                $name = str_replace($token, $string, $name);
            }
            $name = $this->validateNameLength($name);

            $names[$languageCode] = $name;
        }

        return $names;
    }
}

class_alias(NameSchemaService::class, 'eZ\Publish\Core\Repository\Helper\NameSchemaService');
