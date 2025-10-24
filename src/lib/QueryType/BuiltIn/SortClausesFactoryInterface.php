<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\QueryType\BuiltIn;

use Ibexa\Contracts\Core\Repository\Values\Content\Query\SortClause;
use Ibexa\Core\QueryType\BuiltIn\SortSpec\Exception\SyntaxErrorException;

/**
 * @internal
 */
interface SortClausesFactoryInterface
{
    /**
     * @return SortClause[]
     *
     * @throws SyntaxErrorException
     */
    public function createFromSpecification(string $specification): array;
}
