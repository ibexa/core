<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\Persistence\Doctrine\Query;

use Doctrine\DBAL\Platforms\AbstractMySQLPlatform;
use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\DBAL\Platforms\SQLitePlatform;
use Doctrine\ORM\Query\AST\Functions\FunctionNode;
use Doctrine\ORM\Query\AST\Node;
use Doctrine\ORM\Query\Parser;
use Doctrine\ORM\Query\SqlWalker;
use Doctrine\ORM\Query\TokenType;
use RuntimeException;

/**
 * Reads one top-level key out of a JSON document as text.
 *
 * "IBEXA_JSON_TEXT" "(" StringPrimary "," StringPrimary ")"
 *
 * The result is plain text, so it composes with LOWER() and LIKE. A key that is not present and a
 * NULL document both read as NULL.
 *
 * The column has to hold JSON — json or jsonb on PostgreSQL, JSON on MySQL and MariaDB. The
 * expression is the platform's own accessor and nothing else, so it stays eligible for an
 * expression index.
 */
final class JsonTextFunction extends FunctionNode
{
    public const string NAME = 'IBEXA_JSON_TEXT';

    private Node $document;

    private Node $key;

    public function parse(Parser $parser): void
    {
        $parser->match(TokenType::T_IDENTIFIER);
        $parser->match(TokenType::T_OPEN_PARENTHESIS);
        $this->document = $parser->StringPrimary();
        $parser->match(TokenType::T_COMMA);
        $this->key = $parser->StringPrimary();
        $parser->match(TokenType::T_CLOSE_PARENTHESIS);
    }

    public function getSql(SqlWalker $sqlWalker): string
    {
        $platform = $sqlWalker->getConnection()->getDatabasePlatform();

        $value = $sqlWalker->walkStringPrimary($this->document);
        $key = $sqlWalker->walkStringPrimary($this->key);

        if ($platform instanceof PostgreSQLPlatform) {
            return sprintf('%s ->> %s', $value, $key);
        }

        if ($platform instanceof AbstractMySQLPlatform) {
            return sprintf(
                "JSON_UNQUOTE(JSON_EXTRACT(%s, CONCAT('$.\"', %s, '\"')))",
                $value,
                $key,
            );
        }

        if ($platform instanceof SQLitePlatform) {
            return sprintf("json_extract(%s, '$.\"' || %s || '\"')", $value, $key);
        }

        throw new RuntimeException(sprintf(
            '%s() has no implementation for "%s".',
            self::NAME,
            $platform::class,
        ));
    }
}
