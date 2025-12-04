<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Integration\Core\Repository\Filtering;

use Ibexa\Contracts\Core\Test\IbexaTestKernel;
use Symfony\Component\Config\Loader\LoaderInterface;

final class LegacyTestKernel extends IbexaTestKernel
{
    protected function loadServices(LoaderInterface $loader): void
    {
        parent::loadServices($loader);

        $loader->load(__DIR__ . '/Resources/config/services/legacy_sort_clause.yaml');
    }
}
