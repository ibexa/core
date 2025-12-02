<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Bundle\Core\DependencyInjection\Configuration;

use Ibexa\Bundle\Core\DependencyInjection\Configuration\SiteAccessAware\ContextualizerInterface;

abstract class AbstractParser implements ParserInterface
{
    public function preMap(array $config, ContextualizerInterface $contextualizer)
    {
    }

    public function postMap(array $config, ContextualizerInterface $contextualizer)
    {
    }
}
