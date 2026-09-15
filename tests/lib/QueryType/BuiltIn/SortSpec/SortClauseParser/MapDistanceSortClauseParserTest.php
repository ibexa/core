<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Core\QueryType\BuiltIn\SortSpec\SortClauseParser;

use Ibexa\Contracts\Core\Repository\Values\Content\Query;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\SortClause\MapLocationDistance;
use Ibexa\Core\QueryType\BuiltIn\SortSpec\SortClauseParser\MapDistanceSortClauseParser;
use Ibexa\Core\QueryType\BuiltIn\SortSpec\SortSpecParserInterface;
use Ibexa\Core\QueryType\BuiltIn\SortSpec\Token;
use PHPUnit\Framework\TestCase;

final class MapDistanceSortClauseParserTest extends TestCase
{
    private const EXAMPLE_CONTENT_TYPE_ID = 'place';
    private const EXAMPLE_FIELD_ID = 'location';
    private const EXAMPLE_LAT = 50.0647;
    private const EXAMPLE_LON = 19.9450;

    /** @var \Ibexa\Core\QueryType\BuiltIn\SortSpec\SortClauseParser\MapDistanceSortClauseParser */
    private $mapDistanceSortClauseParser;

    protected function setUp(): void
    {
        $this->mapDistanceSortClauseParser = new MapDistanceSortClauseParser();
    }

    public function testParse(): void
    {
        $parser = $this->createMock(SortSpecParserInterface::class);
        $matcher = $this->exactly(5);
        $parser->expects($matcher)
            ->method('match')->willReturnCallback(function (...$parameters) use ($matcher) {
                if ($matcher->numberOfInvocations() === 1) {
                    $this->assertSame(Token::TYPE_ID, $parameters[0]);

                    return new Token(Token::TYPE_ID, self::EXAMPLE_CONTENT_TYPE_ID);
                }
                if ($matcher->numberOfInvocations() === 2) {
                    $this->assertSame(Token::TYPE_DOT, $parameters[0]);

                    return new Token(Token::TYPE_DOT);
                }
                if ($matcher->numberOfInvocations() === 3) {
                    $this->assertSame(Token::TYPE_ID, $parameters[0]);

                    return new Token(Token::TYPE_ID, self::EXAMPLE_FIELD_ID);
                }
                if ($matcher->numberOfInvocations() === 4) {
                    $this->assertSame(Token::TYPE_FLOAT, $parameters[0]);

                    return new Token(Token::TYPE_FLOAT, (string)self::EXAMPLE_LAT);
                }
                if ($matcher->numberOfInvocations() === 5) {
                    $this->assertSame(Token::TYPE_FLOAT, $parameters[0]);

                    return new Token(Token::TYPE_FLOAT, (string)self::EXAMPLE_LON);
                }
            });

        $parser->method('parseSortDirection')->willReturn(Query::SORT_ASC);

        self::assertEquals(
            new MapLocationDistance(
                self::EXAMPLE_CONTENT_TYPE_ID,
                self::EXAMPLE_FIELD_ID,
                self::EXAMPLE_LAT,
                self::EXAMPLE_LON,
                Query::SORT_ASC
            ),
            $this->mapDistanceSortClauseParser->parse($parser, 'map_distance')
        );
    }

    public function testSupports(): void
    {
        self::assertTrue($this->mapDistanceSortClauseParser->supports('map_distance'));
    }
}
