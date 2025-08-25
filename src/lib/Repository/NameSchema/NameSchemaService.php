<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\Repository\NameSchema;

use Ibexa\Contracts\Core\Event\NameSchema\ResolveContentNameSchemaEvent;
use Ibexa\Contracts\Core\Event\NameSchema\ResolveNameSchemaEvent;
use Ibexa\Contracts\Core\Event\NameSchema\ResolveUrlAliasSchemaEvent;
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
     * @param array{limit?: int, sequence?: string} $settings
     */
    protected array $settings;

    private EventDispatcherInterface $eventDispatcher;

    private SchemaIdentifierExtractorInterface $schemaIdentifierExtractor;

    /**
     * @param array{limit?: int, sequence?: string} $settings
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

    public function resolveUrlAliasSchema(Content $content, ?ContentType $contentType = null): array
    {
        $contentType ??= $content->getContentType();
        $schemaName = $contentType->urlAliasSchema ?: $contentType->nameSchema;
        $schemaIdentifiers = $this->schemaIdentifierExtractor->extract($schemaName);

        $event = $this->eventDispatcher->dispatch(
            new ResolveUrlAliasSchemaEvent(
                $schemaIdentifiers,
                $content
            )
        );

        return $this->buildNames($event->getTokenValues(), $schemaName);
    }

    public function resolveContentNameSchema(
        Content $content,
        array $fieldMap = [],
        array $languageCodes = [],
        ?ContentType $contentType = null
    ): array {
        $contentType ??= $content->getContentType();
        $schemaName = $contentType->nameSchema;
        $schemaIdentifiers = $this->schemaIdentifierExtractor->extract($schemaName);

        $event = $this->eventDispatcher->dispatch(
            new ResolveContentNameSchemaEvent(
                $content,
                $schemaIdentifiers,
                $contentType,
                $fieldMap,
                $languageCodes
            )
        );

        return $this->buildNames($event->getTokenValues(), $schemaName);
    }

    public function resolveNameSchema(
        string $nameSchema,
        ContentType $contentType,
        array $fieldMap,
        array $languageCodes
    ): array {
        $schemaIdentifiers = $this->schemaIdentifierExtractor->extract($nameSchema);
        $event = $this->eventDispatcher->dispatch(
            new ResolveNameSchemaEvent(
                $schemaIdentifiers,
                $contentType,
                $fieldMap,
                $languageCodes
            )
        );

        return $this->buildNames($event->getTokenValues(), $nameSchema);
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
        $retNamePattern = $nameSchema;
        $foundGroups = preg_match_all('/\((.+)\)/U', $nameSchema, $groupArray);
        $groupLookupTable = [];

        if ($foundGroups) {
            $i = 0;
            foreach ($groupArray[1] as $group) {
                // Create meta-token for group
                $metaToken = self::META_STRING . $i;

                // Insert the group with its placeholder token
                /** @var string $retNamePattern */
                $retNamePattern = str_replace($group, $metaToken, $retNamePattern);

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
     * @param array<string, array<string, string>> $tokenValues
     *
     * @return array<string, string>
     */
    public function buildNames(array $tokenValues, string $nameSchema): array
    {
        if (empty($tokenValues)) {
            throw new UnresolvedTokenNamesException('$tokenValues', 'is Empty');
        }

        [$filteredNameSchema, $groupLookupTable] = $this->filterNameSchema($nameSchema);
        $tokens = $this->extractTokens($filteredNameSchema);

        $names = [];
        foreach ($tokenValues as $languageCode => $tokenValue) {
            $names[$languageCode] = $this->validateNameLength(
                str_replace(
                    $tokens,
                    array_map(
                        fn (string $token): string => $this->resolveToken($token, $tokenValue, $groupLookupTable),
                        $tokens
                    ),
                    $filteredNameSchema
                )
            );
        }

        return $names;
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
