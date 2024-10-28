<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Core\MVC\Symfony\EventListener;

use Ibexa\Bundle\Core\SiteAccess\Matcher;
use Ibexa\Core\MVC\Symfony\SiteAccess\Matcher\Map\URI;

class TestMatcher extends URI implements Matcher
{
    public function setMatchingConfiguration($matchingConfiguration): void
    {
        // nothing to do
    }
}
