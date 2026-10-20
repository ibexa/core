<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Bundle\Core\SiteAccess;

use Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException;

/**
 * @internal
 *
 * @todo Move to \Ibexa\Core\MVC\Symfony\SiteAccess\Matcher
 */
interface SiteAccessMatcherRegistryInterface
{
    public function setMatcher(
        string $identifier,
        Matcher $matcher
    ): void;

    /**
     * @throws NotFoundException
     */
    public function getMatcher(string $identifier): Matcher;

    public function hasMatcher(string $identifier): bool;
}
