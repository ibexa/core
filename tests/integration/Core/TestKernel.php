<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Integration\Core;

use Ibexa\Contracts\Core\Test\Persistence\Fixture\YamlFixture;
use Ibexa\Contracts\Test\Core\IbexaTestKernel as BaseIbexaTestKernel;

/**
 * Loads the same fixture set {@see \Ibexa\Contracts\Core\Test\IbexaTestKernel} (the package-internal
 * kernel predating ibexa/test-core) already used, instead of the shared kernel's own generic default,
 * so tests already calibrated against it keep passing unchanged.
 */
final class TestKernel extends BaseIbexaTestKernel
{
    public function getFixtures(): iterable
    {
        yield new YamlFixture(__DIR__ . '/Repository/_fixtures/Legacy/data/test_data.yaml');
    }
}
