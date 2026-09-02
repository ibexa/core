<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Integration\Core;

use Ibexa\Contracts\Core\Repository\Repository;
use Ibexa\Contracts\Test\Core\IbexaKernelTestCase;

/**
 * Schema and fixtures are imported once, before the suite runs, by tests/integration/bootstrap.php —
 * this test only needs to boot the kernel.
 *
 * @coversNothing
 */
final class BasicKernelTest extends IbexaKernelTestCase
{
    public function testBasicKernelCompiles(): void
    {
        $this->getIbexaTestCore()->getServiceByClassName(Repository::class);
        $this->expectNotToPerformAssertions();
    }

    public function testRouterIsAvailable(): void
    {
        $router = self::getContainer()->get('router');
        $router->match('/');
        $this->expectNotToPerformAssertions();
    }
}
