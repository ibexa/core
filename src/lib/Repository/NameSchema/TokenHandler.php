<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\Repository\NameSchema;

final class TokenHandler
{
    public const META_STRING = 'EZMETAGROUP_';

    /**
     * Extract all tokens from $namePattern.
     *
     * Example:
     * <code>
     * Text <token> more text ==> <token>
     * </code>
     */
    public function extractTokens(string $nameSchema): array
    {
        preg_match_all('|<([^>]+)>|U', $nameSchema, $tokenArray);

        return $tokenArray[0];
    }

    /**
     * Looks up the value $token should be replaced with and returns this as
     * a string. Meta strings denoting token groups are automatically
     * inferred.
     */
    public function resolveToken(string $token, array $titles, array $groupLookupTable): string
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
            if (array_key_exists($tokenPart, $titles) && $titles[$tokenPart] !== '' && $titles[$tokenPart] !== null) {
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
    public function isTokenGroup(string $identifier): bool
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
     * "&lt;title|text&gt;" ==> array( 'title', 'text' )
     * </code>
     *
     * @param string $token
     *
     * @return array
     */
    public function tokenParts(string $token): array
    {
        return preg_split('/[^\w:]+/', $token, -1, PREG_SPLIT_NO_EMPTY);
    }
}
