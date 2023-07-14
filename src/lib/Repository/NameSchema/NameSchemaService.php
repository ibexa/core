<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\Repository\NameSchema;

use Ibexa\Contracts\Core\Event\ResolveUrlAliasSchemaEvent;
use Ibexa\Contracts\Core\Repository\NameSchema\NameSchemaServiceInterface;
use Ibexa\Contracts\Core\Repository\NameSchema\SchemaIdentifierExtractorInterface;
use Ibexa\Contracts\Core\Repository\Values\Content\Content;
use Ibexa\Contracts\Core\Repository\Values\ContentType\ContentType;
use Ibexa\Core\FieldType\FieldTypeRegistry;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * @internal Meant for internal use by Repository.
 *
 * NameSchemaService is internal service for resolving content name and url alias patterns.
 * This code supports content name pattern groups.
 *
 * Syntax:
 * <code>
 * &lt;attribute_identifier&gt;
 * &lt;attribute_identifier&gt; &lt;2nd-identifier&gt;
 * User text &lt;attribute_identifier&gt;|(&lt;2nd-identifier&gt;&lt;3rd-identifier&gt;)
 * </code>
 *
 * Example:
 * <code>
 * &lt;nickname|(&lt;firstname&gt; &lt;lastname&gt;)&gt;
 * </code>
 *
 * Tokens are looked up from left to right. If a match is found for the
 * leftmost token, the 2nd token will not be used. Tokens are representations
 * of fields. So a match means that the current field has data.
 *
 * Tokens are the field definition identifiers which are used in the class edit-interface.
 */
class NameSchemaService implements NameSchemaServiceInterface
{
    /**
     * The string to use to signify group tokens.
     *
     * @var string
     */
    public const META_STRING = 'EZMETAGROUP_';

    protected FieldTypeRegistry $fieldTypeRegistry;

    /**
     * @param array{limit?: integer, sequence?: string} $settings
     */
    protected array $settings;

    private EventDispatcherInterface $eventDispatcher;

    private SchemaIdentifierExtractorInterface $schemaIdentifierExtractor;

    /**
     * @param array{limit?: integer, sequence?: string} $settings
     */
    public function __construct(
        FieldTypeRegistry $fieldTypeRegistry,
        SchemaIdentifierExtractorInterface $schemaIdentifierExtractor,
        EventDispatcherInterface $eventDispatcher,
        array $settings = []
    ) {
        $this->fieldTypeRegistry = $fieldTypeRegistry;
        // Union makes sure default settings are ignored if provided in argument
        $this->settings = $settings + [
                'limit' => 150,
                'sequence' => '...',
            ];
        $this->eventDispatcher = $eventDispatcher;
        $this->schemaIdentifierExtractor = $schemaIdentifierExtractor;
    }

    public function resolveUrlAliasSchema(Content $content, ContentType $contentType = null): array
    {
        $contentType ??= $content->getContentType();
        $schemaName = $contentType->urlAliasSchema ?: $contentType->nameSchema;
        [$filteredNameSchema, $groupLookupTable] = $this->filterNameSchema($schemaName);
        $schemaIdentifiers = $this->schemaIdentifierExtractor->extract($schemaName);
        $tokens = $this->extractTokens($filteredNameSchema);

        /** @var \Ibexa\Contracts\Core\Event\ResolveUrlAliasSchemaEvent $event */
        $event = $this->eventDispatcher->dispatch(
            new ResolveUrlAliasSchemaEvent(
                $schemaIdentifiers,
                $content
            )
        );
        $names = [];
        $tokenValues = $event->getTokenValues();
        foreach ($tokenValues as $languageCode => $tokenValue) {
            $name = $filteredNameSchema;
            foreach ($tokens as $token) {
                $string = $this->resolveToken($token, $tokenValue, $groupLookupTable);
                $name = str_replace($token, $string, $name);
            }
            $name = $this->validateNameLength($name);

            $names[$languageCode] = $name;
        }

        return $names;
    }

    public function resolveNameSchema(
        Content $content,
        array $fieldMap = [],
        array $languageCodes = [],
        ContentType $contentType = null
    ): array {
        $contentType ??= $content->getContentType();

        $languageCodes = $languageCodes ?: $content->versionInfo->languageCodes;

        return $this->resolve(
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

    /**
     * Convenience method for resolving name schema.
     *
     * @param \Ibexa\Contracts\Core\Repository\Values\Content\Content $content
     * @param array $fieldMap
     * @param array $languageCodes
     *
     * @return array
     */
    protected function mergeFieldMap(Content $content, array $fieldMap, array $languageCodes): array
    {
        if (empty($fieldMap)) {
            return $content->fields;
        }

        $mergedFieldMap = [];

        foreach ($content->fields as $fieldIdentifier => $fieldLanguageMap) {
            foreach ($languageCodes as $languageCode) {
                $mergedFieldMap[$fieldIdentifier][$languageCode]
                    = $fieldMap[$fieldIdentifier][$languageCode] ?? $fieldLanguageMap[$languageCode];
            }
        }

        return $mergedFieldMap;
    }

    public function resolve(string $nameSchema, ContentType $contentType, array $fieldMap, array $languageCodes): array
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

    /**
     * Fetches the list of available Field identifiers in the token and returns
     * an array of their current title value.
     *
     * @param array<string> $schemaIdentifiers
     * @param array<string, array<string, string>> $fieldMap
     *
     * @return string[] Key is the field identifier, value is the title value
     *
     * @see \Ibexa\Core\Repository\Values\ContentType\FieldType::getName()
     */
    protected function getFieldTitles(
        array $schemaIdentifiers,
        ContentType $contentType,
        array $fieldMap,
        string $languageCode
    ): array {
        $fieldTitles = [];

        foreach ($schemaIdentifiers as $fieldDefinitionIdentifier) {
            if (!isset($fieldMap[$fieldDefinitionIdentifier][$languageCode])) {
                continue;
            }

            $fieldDefinition = $contentType->getFieldDefinition($fieldDefinitionIdentifier);
            $persistenceFieldType = $this->fieldTypeRegistry->getFieldType(
                $fieldDefinition->fieldTypeIdentifier
            );

            $fieldTitles[$fieldDefinitionIdentifier] = $persistenceFieldType->getName(
                $fieldMap[$fieldDefinitionIdentifier][$languageCode],
                $fieldDefinition,
                $languageCode
            );
        }

        return $fieldTitles;
    }

    /**
     * Extract all tokens from $namePattern.
     *
     * Example:
     * <code>
     * Text <token> more text ==> <token>
     * </code>
     */
    protected function extractTokens(string $nameSchema): array
    {
        preg_match_all(
            '|<([^>]+)>|U',
            $nameSchema,
            $tokenArray
        );

        return $tokenArray[0];
    }

    /**
     * Looks up the value $token should be replaced with and returns this as
     * a string. Meta strings denoting token groups are automatically
     * inferred.
     */
    protected function resolveToken(string $token, array $titles, array $groupLookupTable): string
    {
        $replaceString = '';
        $tokenParts = $this->tokenParts($token);

        foreach ($tokenParts as $tokenPart) {
            if ($this->isTokenGroup($tokenPart)) {
                $replaceString = $groupLookupTable[$tokenPart];
                $groupTokenArray = $this->extractTokens($replaceString);

                foreach ($groupTokenArray as $groupToken) {
                    $replaceString = str_replace(
                        $groupToken,
                        $this->resolveToken(
                            $groupToken,
                            $titles,
                            $groupLookupTable
                        ),
                        $replaceString
                    );
                }

                // We want to stop after the first matching token part / identifier is found
                // <id1|id2> if id1 has a value, id2 will not be used.
                // In this case id1 or id1 is a token group.
                break;
            }
            if (array_key_exists($tokenPart, $titles)
                && $titles[$tokenPart] !== ''
                && $titles[$tokenPart] !== null
            ) {
                $replaceString = $titles[$tokenPart];
                // We want to stop after the first matching token part / identifier is found
                // <id1|id2> if id1 has a value, id2 will not be used.
                break;
            }
        }

        return $replaceString;
    }

    /**
     * Checks whether $identifier is a placeholder for a token group.
     */
    protected function isTokenGroup(string $identifier): bool
    {
        return strpos($identifier, self::META_STRING) !== false;
    }

    /**
     * Returns the different constituents of $token in an array.
     * The normal case here is that the different identifiers within one token
     * will be tokenized and returned.
     *
     * Example:
     * <code>
     * "&lt;title|text&gt;" ==&gt; array( 'title', 'text' )
     * </code>
     *
     * @param string $token
     *
     * @return array
     */
    protected function tokenParts(string $token): array
    {
        return preg_split('/[^\w:]+/', $token, -1, PREG_SPLIT_NO_EMPTY);
    }

    /**
     * Builds a lookup / translation table for groups in the $namePattern.
     * The groups are referenced with a generated meta-token in the original
     * name pattern.
     *
     * Returns intermediate name pattern where groups are replaced with meta-tokens.
     *
     * @param string $nameSchema
     *
     * @return array{string, array<string, string>}
     */
    protected function filterNameSchema(string $nameSchema): array
    {
        $retNamePattern = '';
        $foundGroups = preg_match_all('/\((.+)\)/U', $nameSchema, $groupArray);
        $groupLookupTable = [];

        if ($foundGroups) {
            $i = 0;
            foreach ($groupArray[1] as $group) {
                // Create meta-token for group
                $metaToken = self::META_STRING . $i;

                // Insert the group with its placeholder token
                /** @var string $retNamePattern */
                $retNamePattern = str_replace($group, $metaToken, $nameSchema);

                // Remove the pattern "(" ")" from the tokens
                $group = str_replace(['(', ')'], '', $group);

                $groupLookupTable[$metaToken] = $group;
                ++$i;
            }
            $nameSchema = $retNamePattern;
        }

        return [$nameSchema, $groupLookupTable];
    }

    /**
     * @return array<string>
     */
    protected function getIdentifiers(string $schemaString): array
    {
        $allTokens = '#<(.*)>#U';
        $identifiers = '#\\W#';

        $tmpArray = [];
        preg_match_all($allTokens, $schemaString, $matches);

        foreach ($matches[1] as $match) {
            $tmpArray[] = preg_split($identifiers, $match, -1, PREG_SPLIT_NO_EMPTY);
        }

        $retArray = [];
        foreach ($tmpArray as $matchGroup) {
            if (is_array($matchGroup)) {
                foreach ($matchGroup as $item) {
                    $retArray[] = $item;
                }
            } else {
                $retArray[] = $matchGroup;
            }
        }

        return $retArray;
    }

    public function validateNameLength(string $name): string
    {
        // Make sure length is not longer than $limit unless it's 0
        if ($this->settings['limit'] && mb_strlen($name) > $this->settings['limit']) {
            $name = rtrim(
                mb_substr($name, 0, $this->settings['limit'] - strlen($this->settings['sequence']))
            ) . $this->settings['sequence'];
        }

        return $name;
    }
}
