<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Bundle\Core\Matcher;

use Ibexa\Contracts\Core\MVC\View\ViewMatcherRegistryInterface;
use Ibexa\Contracts\Core\Repository\Repository;
use Ibexa\Core\MVC\Symfony\Matcher\ClassNameMatcherFactory;
use Ibexa\Core\MVC\Symfony\Matcher\ViewMatcherInterface;

/**
 * A view matcher factory that also accepts services as matchers.
 *
 * If a service id is passed as the MatcherIdentifier, this service will be used for the matching.
 * If a view matcher service is registered with `identifier` attribute, that service will be used for matching. *
 * Otherwise, it will fall back to the class name-based matcher factory.
 */
final class ServiceAwareMatcherFactory extends ClassNameMatcherFactory
{
    private ViewMatcherRegistryInterface $viewMatcherRegistry;

    public function __construct(
        ViewMatcherRegistryInterface $viewMatcherRegistry,
        Repository $repository,
        ?string $relativeNamespace = null,
        array $matchConfig = []
    ) {
        $this->viewMatcherRegistry = $viewMatcherRegistry;

        parent::__construct($repository, $relativeNamespace, $matchConfig);
    }

    /**
     * @param string $matcherIdentifier
     */
    protected function getMatcher($matcherIdentifier): ViewMatcherInterface
    {
        if (strpos($matcherIdentifier, '@') === 0) {
            $matcherIdentifier = substr($matcherIdentifier, 1);
        }

        return $this->viewMatcherRegistry->hasMatcher($matcherIdentifier)
            ? $this->viewMatcherRegistry->getMatcher($matcherIdentifier)
            : parent::getMatcher($matcherIdentifier);
    }
}
