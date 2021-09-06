<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
namespace Ibexa\Tests\Bundle\IO\DependencyInjection\ConfigurationFactory\BinarydataHandler;

use Ibexa\Bundle\IO\DependencyInjection\ConfigurationFactory\BinarydataHandler\Flysystem;
use Ibexa\Tests\Bundle\IO\DependencyInjection\ConfigurationFactory\BaseFlysystemTest;

class FlysystemTest extends BaseFlysystemTest
{
    /**
     * Returns an instance of the tested factory.
     *
     * @return \eZ\Bundle\EzPublishIOBundle\DependencyInjection\ConfigurationFactory\MetadataHandler\Flysystem
     */
    public function provideTestedFactory()
    {
        return new Flysystem();
    }

    /**
     * Returns the expected parent service id.
     */
    public function provideExpectedParentServiceId()
    {
        return 'ezpublish.core.io.binarydata_handler.flysystem';
    }
}

class_alias(FlysystemTest::class, 'eZ\Bundle\EzPublishIOBundle\Tests\DependencyInjection\ConfigurationFactory\BinarydataHandler\FlysystemTest');
