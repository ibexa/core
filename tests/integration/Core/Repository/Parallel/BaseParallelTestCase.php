<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Integration\Core\Repository\Parallel;

use Ibexa\Tests\Core\Repository\Parallel\ParallelProcessList;
use Ibexa\Tests\Integration\Core\Repository\BaseTestCase;
use Jenner\SimpleFork\Process;

abstract class BaseParallelTestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $connection = $this->getRawDatabaseConnection();
        $dbms = $connection->getDatabasePlatform()->getName();

        if ($dbms == 'sqlite') {
            self::markTestSkipped('Can not run parallel test on sqlite');
        }
    }

    protected function addParallelProcess(
        ParallelProcessList $list,
        callable $callback
    ): void {
        $connection = $this->getRawDatabaseConnection();

        $process = new Process(static function () use ($callback, $connection) {
            $connection->connect();
            $callback();
            $connection->close();
        });

        $list->addProcess($process);
    }

    protected function runParallelProcesses(ParallelProcessList $list): void
    {
        $connection = $this->getRawDatabaseConnection();
        // @see https://www.php.net/manual/en/function.pcntl-fork.php#70721
        $connection->close();

        foreach ($list as $process) {
            $process->start();
        }

        foreach ($list as $process) {
            $process->wait();
        }

        $connection->connect();
    }
}
