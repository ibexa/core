<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Integration\Core;

use Ibexa\Contracts\Core\Test\IbexaTestKernel;
use Symfony\Component\Config\Loader\LoaderInterface;

final class CoreTestKernel extends IbexaTestKernel
{
    protected function loadConfiguration(LoaderInterface $loader): void
    {
        parent::loadConfiguration($loader);

        $loader->load(__DIR__ . '/Resources/services/services.php');
    }
}
