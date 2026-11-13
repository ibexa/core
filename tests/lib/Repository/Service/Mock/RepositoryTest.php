<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Core\Repository\Service\Mock;

use Ibexa\Contracts\Core\SiteAccess\ConfigResolverInterface;
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
    public function testBeginTransaction(): void
    {
        $mockedRepository = $this->getRepository();
        $transactionHandlerMock = $this->getTransactionHandlerMock();

        $transactionHandlerMock->expects(
            $this->once()
        )->method(
            'beginTransaction'
        );

        $mockedRepository->beginTransaction();
    }

    /**
     * Test for the commit() method.
     */
    public function testCommit(): void
    {
        $mockedRepository = $this->getRepository();
        $transactionHandlerMock = $this->getTransactionHandlerMock();

        $transactionHandlerMock->expects(
            $this->once()
        )->method(
            'commit'
        );

        $mockedRepository->commit();
    }

    /**
     * Test for the commit() method.
     */
    public function testCommitThrowsRuntimeException(): void
    {
        $this->expectException(\RuntimeException::class);

        $mockedRepository = $this->getRepository();
        $transactionHandlerMock = $this->getTransactionHandlerMock();

        $transactionHandlerMock->expects(
            $this->once()
        )->method(
            'commit'
        )->will(
            $this->throwException(new \Exception())
        );

        $mockedRepository->commit();
    }

    /**
     * Test for the rollback() method.
     */
    public function testRollback(): void
    {
        $mockedRepository = $this->getRepository();
        $transactionHandlerMock = $this->getTransactionHandlerMock();

        $transactionHandlerMock->expects(
            $this->once()
        )->method(
            'rollback'
        );

        $mockedRepository->rollback();
    }

    /**
     * Test for the rollback() method.
     */
    public function testRollbackThrowsRuntimeException(): void
    {
        $this->expectException(\RuntimeException::class);

        $mockedRepository = $this->getRepository();
        $transactionHandlerMock = $this->getTransactionHandlerMock();

        $transactionHandlerMock->expects(
            $this->once()
        )->method(
            'rollback'
        )->will(
            $this->throwException(new \Exception())
        );

        $mockedRepository->rollback();
    }

    public function testGetContentLanguageServiceUsesConfiguredLanguages(): void
    {
        $configResolver = self::createStub(ConfigResolverInterface::class);
        $configResolver->method('hasParameter')->willReturnMap([['languages', null, null, true]]);
        $configResolver->method('getParameter')->willReturnMap([['languages', null, null, ['pol-PL', 'eng-GB']]]);

        $repository = $this->getRepository(['languages' => ['ger-DE']], $configResolver);

        self::assertSame('pol-PL', $repository->getContentLanguageService()->getDefaultLanguageCode());
    }
}
