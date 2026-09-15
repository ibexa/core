<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Core\QueryType\BuiltIn\SortSpec\SortClauseParser;

use Ibexa\Contracts\Core\Repository\Values\Content\Query;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\SortClause\Field;
use Ibexa\Core\QueryType\BuiltIn\SortSpec\SortClauseParser\FieldSortClauseParser;
use Ibexa\Core\QueryType\BuiltIn\SortSpec\SortSpecParserInterface;
use Ibexa\Core\QueryType\BuiltIn\SortSpec\Token;
use PHPUnit\Framework\TestCase;

final class FieldSortClauseParserTest extends TestCase
{
    private const EXAMPLE_CONTENT_TYPE_ID = 'article';
    private const EXAMPLE_FIELD_ID = 'title';

    /** @var \Ibexa\Core\QueryType\BuiltIn\SortSpec\SortClauseParser\FieldSortClauseParser */
    private $fieldSortClauseParser;

    protected function setUp(): void
    {
        $this->fieldSortClauseParser = new FieldSortClauseParser();
    }

    public function testParse(): void
    {
        $parser = $this->createMock(SortSpecParserInterface::class);
        $matcher = $this->exactly(3);
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
            });

        $parser->method('parseSortDirection')->willReturn(Query::SORT_ASC);

        self::assertEquals(
            new Field(self::EXAMPLE_CONTENT_TYPE_ID, self::EXAMPLE_FIELD_ID, Query::SORT_ASC),
            $this->fieldSortClauseParser->parse($parser, 'field')
        );
    }

    public function testSupports(): void
    {
        self::assertTrue($this->fieldSortClauseParser->supports('field'));
    }
}
