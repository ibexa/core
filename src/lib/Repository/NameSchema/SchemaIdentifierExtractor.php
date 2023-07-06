<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\Repository\NameSchema;

use Ibexa\Contracts\Core\Repository\NameSchema\SchemaIdentifierExtractorInterface;

final class SchemaIdentifierExtractor implements SchemaIdentifierExtractorInterface
{
    /**
     * @return array<string, array<string, string>>
     *
     * @example
     *  $extractor = new SchemaIdentifierExtractor();
     *  $schemaString = '<foo|bar>-<attribute:bar>-<attribute:baz>';
     *  $result = $extractor->extract($schemaString);
     *  // $result will be:
     *  // [
     *  //    'field' => ['foo', 'bar'],
     *  //    'attribute' => ['bar', 'baz'],
     *  // ]
     */
    public function extract(string $schemaString): array
    {
        $allTokens = '/<([^>]+)>/';

        if (false === preg_match_all($allTokens, $schemaString, $matches)) {
            return [];
        }

        $strategyIdentifiers = [];
        foreach ($matches[1] as $tokenExpression) {
            $tokens = explode('|', $tokenExpression);

            foreach ($tokens as $token) {
                $strategyToken = explode(':', $token, 2);

                if (count($strategyToken) === 2) {
                    [$strategy, $token] = $strategyToken;
                } else {
                    $token = $strategyToken[0];
                    $strategy = 'field';
                }

                $strategyIdentifiers[$strategy][] = $token;
            }
        }

        return $strategyIdentifiers;
    }
}
