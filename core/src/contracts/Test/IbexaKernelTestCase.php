<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\Core\Test;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * @internal For core tests only. Use \Ibexa\Contracts\Test\Core\IbexaKernelTestCase from ibexa/test-core instead.
 */
abstract class IbexaKernelTestCase extends KernelTestCase
{
    use IbexaKernelTestTrait;
}
