<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\QueryType\BuiltIn;

use Ibexa\Contracts\Core\Repository\Values\Content\Query\SortClause;
use Ibexa\Core\QueryType\BuiltIn\SortSpec\Exception\SyntaxErrorException;
use Ibexa\Core\QueryType\BuiltIn\SortSpec\SortClauseParserInterface;
use Ibexa\Core\QueryType\BuiltIn\SortSpec\SortSpecLexer;
use Ibexa\Core\QueryType\BuiltIn\SortSpec\SortSpecParser;

/**
 * @internal
 */
final class SortClausesFactory implements SortClausesFactoryInterface
{
    /** @var SortClauseParserInterface */
    private $sortClauseParser;

    public function __construct(SortClauseParserInterface $sortClauseArgsParser)
    {
        $this->sortClauseParser = $sortClauseArgsParser;
    }

    /**
     * @throws SyntaxErrorException
     *
     * @return SortClause[]
     */
    public function createFromSpecification(string $specification): array
    {
        $lexer = new SortSpecLexer();
        $lexer->tokenize($specification);

        $parser = new SortSpecParser($this->sortClauseParser, $lexer);

        return $parser->parseSortClausesList();
    }
}
