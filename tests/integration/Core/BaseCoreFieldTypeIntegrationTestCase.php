<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Integration\Core;

use Doctrine\DBAL\Connection;
use ErrorException;
use Ibexa\Tests\Integration\Core\Repository\BaseTestCase as APIBaseTest;

/**
 * Base class for non-API Field Type integration tests (like Gateway w/ DBMS integration).
 */
abstract class BaseCoreFieldTypeIntegrationTestCase extends APIBaseTest
{
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        if (!isset($_ENV['setupFactory'])) {
            self::markTestSkipped(
                static::class . ' is an integration test and requires setupFactory env setting. '
                . 'Use phpunit-integration-legacy.xml configuration to run it'
            );
        }
    }

    /**
     * Return the database connection from the service container.
     *
     * @return \Doctrine\DBAL\Connection|object
     */
    protected function getDatabaseConnection(): Connection
    {
        try {
            return $this->getSetupFactory()->getServiceContainer()->get(
                'ibexa.persistence.connection'
            );
        } catch (ErrorException $e) {
            self::fail(
                sprintf(
                    '%s: %s in %s:%d',
                    __METHOD__,
                    $e->getMessage(),
                    $e->getFile(),
                    $e->getLine()
                )
            );
        }
    }
}
