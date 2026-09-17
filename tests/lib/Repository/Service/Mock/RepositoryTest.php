<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Core\Repository\Service\Mock;

use Ibexa\Core\Repository\Repository;
use Ibexa\Tests\Core\Repository\Service\Mock\Base as BaseServiceMockTest;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Mock test case for Repository.
 */
#[CoversClass(Repository::class)]
class RepositoryTest extends BaseServiceMockTest
{
    /**
     * Test for the beginTransaction() method.
     */
    public function testBeginTransaction()
    {
        $mockedRepository = $this->getRepository();
        $transactionHandlerMock = $this->getTransactionHandlerMock();

        $transactionHandlerMock->expects(
            self::once()
        )->method(
            'beginTransaction'
        );

        $mockedRepository->beginTransaction();
    }

    /**
     * Test for the commit() method.
     */
    public function testCommit()
    {
        $mockedRepository = $this->getRepository();
        $transactionHandlerMock = $this->getTransactionHandlerMock();

        $transactionHandlerMock->expects(
            self::once()
        )->method(
            'commit'
        );

        $mockedRepository->commit();
    }

    /**
     * Test for the commit() method.
     */
    public function testCommitThrowsRuntimeException()
    {
        $this->expectException(\RuntimeException::class);

        $mockedRepository = $this->getRepository();
        $transactionHandlerMock = $this->getTransactionHandlerMock();

        $transactionHandlerMock->expects(
            self::once()
        )->method(
            'commit'
        )->will(
            self::throwException(new \Exception())
        );

        $mockedRepository->commit();
    }

    /**
     * Test for the rollback() method.
     */
    public function testRollback()
    {
        $mockedRepository = $this->getRepository();
        $transactionHandlerMock = $this->getTransactionHandlerMock();

        $transactionHandlerMock->expects(
            self::once()
        )->method(
            'rollback'
        );

        $mockedRepository->rollback();
    }

    /**
     * Test for the rollback() method.
     */
    public function testRollbackThrowsRuntimeException()
    {
        $this->expectException(\RuntimeException::class);

        $mockedRepository = $this->getRepository();
        $transactionHandlerMock = $this->getTransactionHandlerMock();

        $transactionHandlerMock->expects(
            self::once()
        )->method(
            'rollback'
        )->will(
            self::throwException(new \Exception())
        );

        $mockedRepository->rollback();
    }
}
