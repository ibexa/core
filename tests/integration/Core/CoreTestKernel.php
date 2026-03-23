<?php

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
