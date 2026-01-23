<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Bundle\Core\URLChecker;

use Ibexa\Contracts\Core\Repository\Values\URL\URL;

interface URLHandlerInterface
{
    /**
     * Validates given list of URLs.
     *
     * @param URL[] $urls
     */
    public function validate(array $urls);
}
