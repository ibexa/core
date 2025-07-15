<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Bundle\IO\DependencyInjection\ConfigurationFactory\MetadataHandler;

use Ibexa\Bundle\IO\DependencyInjection\ConfigurationFactory;
use Ibexa\Bundle\IO\DependencyInjection\ConfigurationFactory\MetadataHandler\Flysystem;
use Ibexa\Tests\Bundle\IO\DependencyInjection\ConfigurationFactory\BaseFlysystemTestCase;

class FlysystemTest extends BaseFlysystemTestCase
{
    public function provideTestedFactory(): ConfigurationFactory
    {
        return new Flysystem();
    }

    public function provideExpectedParentServiceId(): string
    {
        return 'ibexa.core.io.metadata_handler.flysystem';
    }
}
