<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\Core\MVC\View;

use Ibexa\Core\MVC\Symfony\Matcher\ViewMatcherInterface;

interface ViewMatcherRegistryInterface
{
    public function setMatcher(string $matcherIdentifier, ViewMatcherInterface $matcher): void;

    public function getMatcher(string $matcherIdentifier): ViewMatcherInterface;

    public function hasMatcher(string $matcherIdentifier): bool;
}
