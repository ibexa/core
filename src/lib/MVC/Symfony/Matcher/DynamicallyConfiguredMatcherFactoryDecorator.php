<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Core\MVC\Symfony\Matcher;

use Ibexa\Contracts\Core\SiteAccess\ConfigResolverInterface;
use Ibexa\Core\MVC\Symfony\View\View;

/**
 * Injects dynamic configuration before every matching operation.
 */
class DynamicallyConfiguredMatcherFactoryDecorator implements MatcherFactoryInterface
{
    private MatcherFactoryInterface $innerConfigurableMatcherFactory;

    private ConfigResolverInterface $configResolver;

    private string $parameterName;

    private ?string $namespace;

    private ?string $scope;

    public function __construct(
        MatcherFactoryInterface $innerConfigurableMatcherFactory,
        ConfigResolverInterface $configResolver,
        string $parameterName,
        ?string $namespace = null,
        ?string $scope = null
    ) {
        $this->innerConfigurableMatcherFactory = $innerConfigurableMatcherFactory;
        $this->configResolver = $configResolver;
        $this->parameterName = $parameterName;
        $this->namespace = $namespace;
        $this->scope = $scope;
    }

    public function match(View $view)
    {
        $matchConfig = $this->configResolver->getParameter($this->parameterName, $this->namespace, $this->scope);
        $this->innerConfigurableMatcherFactory->setMatchConfig($matchConfig);

        return $this->innerConfigurableMatcherFactory->match($view);
    }
}
