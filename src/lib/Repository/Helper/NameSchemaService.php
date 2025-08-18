<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
namespace Ibexa\Core\Repository\Helper;

use Ibexa\Contracts\Core\Persistence\Content\Type as SPIContentType;
use Ibexa\Contracts\Core\Persistence\Content\Type\Handler as ContentTypeHandler;
use Ibexa\Contracts\Core\Repository\Values\Content\Content;
use Ibexa\Contracts\Core\Repository\Values\ContentType\ContentType;
use Ibexa\Core\Base\Exceptions\InvalidArgumentType;
use Ibexa\Core\FieldType\FieldTypeRegistry;
use Ibexa\Core\Repository\Mapper\ContentTypeDomainMapper;
use Ibexa\Core\Repository\NameSchema\NameSchemaService as NativeNameSchemaService;
use Ibexa\Core\Repository\NameSchema\SchemaIdentifierExtractor;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * @deprecated inject \Ibexa\Contracts\Core\Repository\NameSchema\NameSchemaServiceInterface instead.
 * @see \Ibexa\Contracts\Core\Repository\NameSchema\NameSchemaServiceInterface
 */
class NameSchemaService extends NativeNameSchemaService
{
    protected ContentTypeHandler $contentTypeHandler;

    protected ContentTypeDomainMapper $contentTypeDomainMapper;

    public function __construct(
        ContentTypeHandler $contentTypeHandler,
        ContentTypeDomainMapper $contentTypeDomainMapper,
        FieldTypeRegistry $fieldTypeRegistry,
        EventDispatcherInterface $eventDispatcher,
        array $settings = []
    ) {
        $this->settings = $settings + [
                'limit' => 150,
                'sequence' => '...',
            ];

        parent::__construct(
            $fieldTypeRegistry,
            new SchemaIdentifierExtractor(),
            $eventDispatcher,
            $settings
        );
        $this->contentTypeHandler = $contentTypeHandler;
        $this->contentTypeDomainMapper = $contentTypeDomainMapper;
    }

    public function resolveUrlAliasSchema(Content $content, ?ContentType $contentType = null): array
    {
        $contentType = $contentType ?? $content->getContentType();

        return $this->resolveUrlAliasSchema(
            $content,
            $contentType
        );
    }

    public function resolveContentNameSchema(
        Content $content,
        array $fieldMap = [],
        array $languageCodes = [],
        ?ContentType $contentType = null
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
    ): array {
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

    /**
     * @param \Ibexa\Contracts\Core\Repository\Values\Content\Content $content
     * @param array<int|string, array<string, \Ibexa\Contracts\Core\FieldType\Value>>  $fieldMap
     * @param array<string> $languageCodes
     *
     * @return array<int|string, array<string, \Ibexa\Contracts\Core\FieldType\Value>>
     */
    protected function mergeFieldMap(Content $content, array $fieldMap, array $languageCodes): array
    {
        $mergedFieldMap = [];

        foreach ($content->fields as $fieldIdentifier => $fieldLanguageMap) {
            foreach ($languageCodes as $languageCode) {
                $mergedFieldMap[$fieldIdentifier][$languageCode] = $fieldMap[$fieldIdentifier][$languageCode];
            }
        }

        return $mergedFieldMap;
    }

    /**
     * Fetches the list of available Field identifiers in the token and returns
     * an array of their current title value.
     *
     * @param array<string> $schemaIdentifiers
     * @param \Ibexa\Contracts\Core\Persistence\Content\Type|\Ibexa\Contracts\Core\Repository\Values\ContentType\ContentType $contentType
     * @param array<int|string, array<string, \Ibexa\Contracts\Core\FieldType\Value>>  $fieldMap
     * @param string $languageCode
     *
     * @return array<string> Key is the field identifier, value is the title value
     *
     * @throws \Ibexa\Core\Base\Exceptions\InvalidArgumentType
     *
     * @see \Ibexa\Core\Repository\Values\ContentType\FieldType::getName()
     */
    protected function getFieldTitles(array $schemaIdentifiers, $contentType, array $fieldMap, $languageCode): array
    {
        $fieldTitles = [];

        foreach ($schemaIdentifiers as $fieldDefinitionIdentifier) {
            if (!isset($fieldMap[$fieldDefinitionIdentifier][$languageCode])) {
                continue;
            }
            if ($contentType instanceof SPIContentType) {
                $fieldDefinition = null;
                foreach ($contentType->fieldDefinitions as $spiFieldDefinition) {
                    if ($spiFieldDefinition->identifier === $fieldDefinitionIdentifier) {
                        $fieldDefinition = $this->contentTypeDomainMapper->buildFieldDefinitionDomainObject(
                            $spiFieldDefinition,
                            // This is probably not main language, but as we don't expose it, it's ok for now.
                            $languageCode
                        );
                        break;
                    }
                }
                if ($fieldDefinition === null) {
                    $fieldTitles[$fieldDefinitionIdentifier] = '';
                    continue;
                }
            } elseif ($contentType instanceof ContentType) {
                $fieldDefinition = $contentType->getFieldDefinition($fieldDefinitionIdentifier);
            } else {
                throw new InvalidArgumentType('$contentType', 'API or SPI variant of a content type');
            }
            $fieldTypeService = $this->fieldTypeRegistry->getFieldType(
                $fieldDefinition->fieldTypeIdentifier
            );
            $fieldTitles[$fieldDefinitionIdentifier] = $fieldTypeService->getName(
                $fieldMap[$fieldDefinitionIdentifier][$languageCode],
                $fieldDefinition,
                $languageCode
            );
        }

        return $fieldTitles;
    }
}

class_alias(NameSchemaService::class, 'eZ\Publish\Core\Repository\Helper\NameSchemaService');
