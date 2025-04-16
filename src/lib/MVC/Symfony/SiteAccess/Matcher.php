<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Core\MVC\Symfony\SiteAccess;

use Ibexa\Core\MVC\Symfony\Routing\SimplifiedRequest;

/**
 * Interface for SiteAccess matchers.
 */
interface Matcher
{
    /**
     * Injects the request object to match against.
     *
     * @param \Ibexa\Core\MVC\Symfony\Routing\SimplifiedRequest $request
     */
    public function setRequest(SimplifiedRequest $request): void;

    /**
     * Returns matched SiteAccess or false if no SiteAccess could be matched.
     */
    public function match(): string|bool;

    /**
     * Returns the matcher's name.
     * This information will be stored in the SiteAccess object itself to quickly be able to identify the matcher type.
     */
    public function getName(): string;
}
