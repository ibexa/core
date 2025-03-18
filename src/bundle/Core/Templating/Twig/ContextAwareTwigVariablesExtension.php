<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Bundle\Core\Templating\Twig;

use Ibexa\Contracts\Core\SiteAccess\ConfigResolverInterface;
use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;

final class ContextAwareTwigVariablesExtension extends AbstractExtension implements GlobalsInterface
{
    private ConfigResolverInterface $configResolver;

    public function __construct(
        ConfigResolverInterface $configResolver
    ) {
        $this->configResolver = $configResolver;
    }

    public function getGlobals(): array
    {
        return $this->configResolver->getParameter('twig_variables');
    }
}
