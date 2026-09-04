<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Integration\Core;

use Ibexa\Contracts\Solr\Test\IbexaSolrTestKernel;
use Symfony\Component\Config\Loader\LoaderInterface;

final class CoreSolrTestKernel extends IbexaSolrTestKernel
{
    protected function loadConfiguration(LoaderInterface $loader): void
    {
        parent::loadConfiguration($loader);

        $loader->load(__DIR__ . '/Resources/services/services.php');
    }
}
