<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Core\QueryType;

use Ibexa\Contracts\Core\Repository\Values\Content\Query;

/**
 * A QueryType is a pre-defined content or location query.
 *
 * QueryTypes must be registered with the service container using the `ibexa.query_type` service tag.
 */
interface QueryType
{
    /**
     * Builds and returns the Query object.
     *
     * @param array $parameters A hash of parameters that will be used to build the Query
     *
     * @return Query
     */
    public function getQuery(array $parameters = []);

    /**
     * Returns an array listing the parameters supported by the QueryType.
     *
     * @return array
     */
    public function getSupportedParameters();

    /**
     * Returns the QueryType name.
     *
     * @return string
     */
    public static function getName();
}
