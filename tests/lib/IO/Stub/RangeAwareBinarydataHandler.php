<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Core\IO\Stub;

use Ibexa\Core\IO\IOBinarydataHandler;
use Ibexa\Core\IO\RangeAwareIOBinarydataHandlerInterface;

/**
 * Lets tests mock a binarydata handler implementing both interfaces without createMock([...]),
 * which is removed in PHPUnit 10.
 */
interface RangeAwareBinarydataHandler extends IOBinarydataHandler, RangeAwareIOBinarydataHandlerInterface {}
