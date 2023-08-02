<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\Repository\NameSchema;

final class NameSchemaFilter
{
    public const META_STRING = 'EZMETAGROUP_';

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
    public function filterNameSchema(string $nameSchema): array
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
}
