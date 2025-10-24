<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Contracts\Core;

use Ibexa\Contracts\Core\Repository\Repository;

/**
 * Container interface.
 *
 * Starting point for getting all Public API's
 */
interface Container
{
    /**
     * Get Repository object.
     *
     * Public API for
     *
     * @return Repository
     */
    public function getRepository();
}
