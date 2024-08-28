<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Bundle\Core\DependencyInjection\Stub\QueryTypeBundle\QueryType;

use Ibexa\Core\QueryType\QueryType;

class TestQueryType implements QueryType
{
    public function getQuery(array $parameters = [])
    {
    }

    public function getSupportedParameters()
    {
    }

    public static function getName(): string
    {
        return 'Test:Test';
    }
}
