<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Bundle\Core\Matcher;

use Ibexa\Contracts\Core\MVC\View\ViewMatcherRegistryInterface;
use Ibexa\Core\Base\Exceptions\NotFoundException;
use Ibexa\Core\MVC\Symfony\Matcher\ViewMatcherInterface;

/**
 * @internal
 */
final class ViewMatcherRegistry implements ViewMatcherRegistryInterface
{
    /** @var ViewMatcherInterface[] */
    private $matchers;

    /**
     * @param iterable<ViewMatcherInterface> $matchers
     */
    public function __construct(iterable $matchers = [])
    {
        $this->matchers = [];
        foreach ($matchers as $identifier => $matcher) {
            $this->matchers[$identifier] = $matcher;
        }
    }

    public function setMatcher(
        string $matcherIdentifier,
        ViewMatcherInterface $matcher
    ): void {
        $this->matchers[$matcherIdentifier] = $matcher;
    }

    /**
     * @param string $matcherIdentifier
     *
     * @return ViewMatcherInterface
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException
     */
    public function getMatcher(string $matcherIdentifier): ViewMatcherInterface
    {
        if (!isset($this->matchers[$matcherIdentifier])) {
            throw new NotFoundException('Matcher', $matcherIdentifier);
        }

        return $this->matchers[$matcherIdentifier];
    }

    public function hasMatcher(string $matcherIdentifier): bool
    {
        return isset($this->matchers[$matcherIdentifier]);
    }
}
