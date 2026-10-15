<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Contracts\Core\Test\Repository;

use Ibexa\Contracts\Core\Repository\Repository;
use Ibexa\Core\Base\ServiceContainer;
use Ibexa\Tests\Integration\Core\Repository\IdManager;

/**
 * A Test Factory is used to setup the infrastructure for a tests, based on a
 * specific repository implementation to test.
 */
abstract class SetupFactory
{
    /**
     * Returns a configured repository for testing.
     *
     * @param bool $initializeFromScratch if the back end should be initialized
     *                                    from scratch or re-used
     *
     * @return Repository
     */
    abstract public function getRepository($initializeFromScratch = true);

    /**
     * Returns a repository specific ID manager.
     *
     * @return IdManager
     */
    abstract public function getIdManager();

    /**
     * Returns a config value for $configKey.
     *
     * @param string $configKey
     *
     * @throws \Exception if $configKey could not be found.
     *
     * @return mixed
     */
    abstract public function getConfigValue($configKey);

    /**
     * Returns the service container used for initialization of the repository.
     *
     * Most tests should not use this at all!!
     *
     * @return ServiceContainer
     */
    abstract public function getServiceContainer();
}

class_alias(SetupFactory::class, 'eZ\Publish\API\Repository\Tests\SetupFactory');
