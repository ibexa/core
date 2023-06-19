<?php
/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\Repository\Helper;

class SchemaIdentifierExtractor
{
    public function extract($schemaString)
    {
        $allTokens = '/<(.*?)>/';


        if (false === preg_match_all($allTokens, $schemaString, $matches)) {
            return [];
        };

        $strategyIdentifiers = [];
        foreach ($matches[1] as $tokenExpression) {
            $strategyToken = explode(':', $tokenExpression, 2);
            if (count($strategyToken) === 2) {
                [$strategy, $token] = $strategyToken;
            } else {
                $token = $strategyToken[0];
                $strategy = 'field';
            }

            $strategyIdentifiers[$strategy] = array_merge($strategyIdentifiers[$strategy] ?? [], explode('|', $token));

        }

        return $strategyIdentifiers;
    }
}
