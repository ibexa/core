<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\Core\Test\Repository;

use Ibexa\Contracts\Core\Repository\Repository;
use Ibexa\Core\Base\ServiceContainer;
use Ibexa\Tests\Integration\Core\Repository\IdManager;

/**
 * A Test Factory is used to set up the infrastructure for a tests, based on a
 * specific repository implementation to test.
 */
abstract class SetupFactory
{
    /**
     * Returns a configured repository for testing.
     *
     * @param bool $initializeFromScratch if the back end should be initialized
     *                                    from scratch or re-used
     */
    abstract public function getRepository(bool $initializeFromScratch = true): Repository;

    /**
     * Returns a repository-specific ID manager.
     */
    abstract public function getIdManager(): IdManager;

    /**
     * Returns a config value for $configKey.
     *
     * @throws \Exception if $configKey could not be found.
     */
    abstract public function getConfigValue(string $configKey): mixed;

    /**
     * Returns the service container used for initialization of the repository.
     *
     * Most tests should not use this at all!!
     */
    abstract public function getServiceContainer(): ServiceContainer;
}
