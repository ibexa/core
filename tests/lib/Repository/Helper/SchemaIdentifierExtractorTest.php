<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Core\Repository\Helper;

use Ibexa\Core\Repository\Helper\SchemaIdentifierExtractor;
use PHPUnit\Framework\TestCase;

class SchemaIdentifierExtractorTest extends TestCase
{
    public function testExtract()
    {
        $extractor = new SchemaIdentifierExtractor();

        // Test with a valid schema string
        $schemaString = '<field:username>|<relation:author>|<field:title>';
        $expectedResult = [
            'field' => ['username', 'title'],
            'relation' => ['author'],
        ];
        $this->assertEquals($expectedResult, $extractor->extract($schemaString));

        // Test with an empty schema string
        $schemaString = '';
        $expectedResult = [];
        $this->assertEquals($expectedResult, $extractor->extract($schemaString));

        // Test with a schema string without tokens
        $schemaString = 'This is a plain text.';
        $expectedResult = [];
        $this->assertEquals($expectedResult, $extractor->extract($schemaString));

        // Test with a schema string containing invalid tokens
        $schemaString = '<field:username>|<invalid_token>|<relation:author>';
        $expectedResult = [
            'field' => ['username'],
            'relation' => ['author'],
        ];
        $this->assertEquals($expectedResult, $extractor->extract($schemaString));
    }
}
