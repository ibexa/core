<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Core\Repository\Service\Mock;

use Exception;
use Ibexa\Contracts\Core\Persistence\UserPreference\UserPreference;
use Ibexa\Contracts\Core\Persistence\UserPreference\UserPreferenceSetStruct;
use Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException;
use Ibexa\Contracts\Core\Repository\PermissionResolver;
use Ibexa\Contracts\Core\Repository\Values\UserPreference\UserPreference as APIUserPreference;
use Ibexa\Contracts\Core\Repository\Values\UserPreference\UserPreferenceSetStruct as APIUserPreferenceSetStruct;
use Ibexa\Core\Repository\UserPreferenceService;
use Ibexa\Core\Repository\Values\User\UserReference;
use Ibexa\Tests\Core\Repository\Service\Mock\Base as BaseServiceMockTest;
use PHPUnit\Framework\MockObject\MockObject;

class UserPreferenceTest extends BaseServiceMockTest
{
    public const CURRENT_USER_ID = 14;
    public const USER_PREFERENCE_NAME = 'setting';
    public const USER_PREFERENCE_VALUE = 'value';

    /** @var \Ibexa\Contracts\Core\Persistence\UserPreference\Handler|\PHPUnit\Framework\MockObject\MockObject */
    private $userSPIPreferenceHandler;

    protected function setUp(): void
    {
        parent::setUp();
        $this->userSPIPreferenceHandler = $this->getPersistenceMockHandler('UserPreference\\Handler');
        $permissionResolverMock = $this->createMock(PermissionResolver::class);
        $permissionResolverMock
            ->method('getCurrentUserReference')
            ->willReturn(new UserReference(self::CURRENT_USER_ID));
        $repository = $this->getRepositoryMock();
        $repository
            ->method('getPermissionResolver')
            ->willReturn($permissionResolverMock);
    }

    /**
     * @covers \Ibexa\Contracts\Core\Repository\UserPreferenceService::setUserPreference()
     */
    public function testSetUserPreference(): void
    {
        $apiUserPreferenceSetStruct = new APIUserPreferenceSetStruct([
            'name' => 'setting',
            'value' => 'value',
        ]);

        $this->assertTransactionIsCommitted(function (): void {
            $this->userSPIPreferenceHandler
                ->expects($this->once())
                ->method('setUserPreference')
                ->willReturnCallback(function (UserPreferenceSetStruct $setStruct): UserPreference {
                    $this->assertEquals(self::USER_PREFERENCE_NAME, $setStruct->name);
                    $this->assertEquals(self::USER_PREFERENCE_VALUE, $setStruct->value);
                    $this->assertEquals(self::CURRENT_USER_ID, $setStruct->userId);

                    return new UserPreference();
                });
        });

        $this->createAPIUserPreferenceService()->setUserPreference([$apiUserPreferenceSetStruct]);
    }

    /**
     * @covers \Ibexa\Contracts\Core\Repository\UserPreferenceService::setUserPreference
     */
    public function testSetUserPreferenceThrowsInvalidArgumentException(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $apiUserPreferenceSetStruct = new APIUserPreferenceSetStruct([
            'value' => 'value',
        ]);

        $this->assertTransactionIsNotStarted(function (): void {
            $this->userSPIPreferenceHandler->expects($this->never())->method('setUserPreference');
        });

        $this->createAPIUserPreferenceService()->setUserPreference([$apiUserPreferenceSetStruct]);
    }

    /**
     * @covers \Ibexa\Contracts\Core\Repository\UserPreferenceService::setUserPreference
     */
    public function testSetUserPreferenceWithRollback(): void
    {
        $this->expectException(Exception::class);

        $apiUserPreferenceSetStruct = new APIUserPreferenceSetStruct([
            'name' => 'setting',
            'value' => 'value',
        ]);

        $this->assertTransactionIsRollback(function (): void {
            $this->userSPIPreferenceHandler
                ->expects($this->once())
                ->method('setUserPreference')
                ->willThrowException($this->createMock(Exception::class));
        });

        $this->createAPIUserPreferenceService()->setUserPreference([$apiUserPreferenceSetStruct]);
    }

    /**
     * @covers \Ibexa\Contracts\Core\Repository\UserPreferenceService::getUserPreference()
     */
    public function testGetUserPreference(): void
    {
        $userPreferenceName = 'setting';
        $userPreferenceValue = 'value';

        $this->userSPIPreferenceHandler
            ->expects(self::once())
            ->method('getUserPreferenceByUserIdAndName')
            ->with(self::CURRENT_USER_ID, $userPreferenceName)
            ->willReturn(new UserPreference([
                'name' => $userPreferenceName,
                'value' => $userPreferenceValue,
                'userId' => self::CURRENT_USER_ID,
            ]));

        $APIUserPreference = $this->createAPIUserPreferenceService()->getUserPreference($userPreferenceName);
        $expected = new APIUserPreference([
            'name' => $userPreferenceName,
            'value' => $userPreferenceValue,
        ]);
        self::assertEquals($expected, $APIUserPreference);
    }

    /**
     * @covers \Ibexa\Contracts\Core\Repository\UserPreferenceService::loadUserPreferences
     */
    public function testLoadUserPreferences(): void
    {
        $offset = 0;
        $limit = 25;
        $expectedTotalCount = 10;

        $expectedItems = array_map(function (): APIUserPreference {
            return $this->createAPIUserPreference();
        }, range(1, $expectedTotalCount));

        $this->userSPIPreferenceHandler
            ->expects(self::once())
            ->method('countUserPreferences')
            ->with(self::CURRENT_USER_ID)
            ->willReturn($expectedTotalCount);

        $this->userSPIPreferenceHandler
            ->expects(self::once())
            ->method('loadUserPreferences')
            ->with(self::CURRENT_USER_ID, $offset, $limit)
            ->willReturn(array_map(static function ($locationId): UserPreference {
                return new UserPreference([
                    'name' => 'setting',
                    'value' => 'value',
                ]);
            }, range(1, $expectedTotalCount)));

        $userPreferences = $this->createAPIUserPreferenceService()->loadUserPreferences($offset, $limit);

        self::assertEquals($expectedTotalCount, $userPreferences->totalCount);
        self::assertEquals($expectedItems, $userPreferences->items);
    }

    /**
     * @covers \Ibexa\Contracts\Core\Repository\UserPreferenceService::getUserPreferenceCount()
     */
    public function testGetUserPreferenceCount(): void
    {
        $expectedTotalCount = 10;

        $this->userSPIPreferenceHandler
            ->expects(self::once())
            ->method('countUserPreferences')
            ->with(self::CURRENT_USER_ID)
            ->willReturn($expectedTotalCount);

        $APIUserPreference = $this->createAPIUserPreferenceService()->getUserPreferenceCount();

        self::assertEquals($expectedTotalCount, $APIUserPreference);
    }

    /**
     * @return \Ibexa\Contracts\Core\Repository\UserPreferenceService|\PHPUnit\Framework\MockObject\MockObject
     */
    private function createAPIUserPreferenceService(array $methods = null): MockObject
    {
        return $this
            ->getMockBuilder(UserPreferenceService::class)
            ->setConstructorArgs([$this->getRepositoryMock(), $this->userSPIPreferenceHandler])
            ->setMethods($methods)
            ->getMock();
    }

    private function assertTransactionIsCommitted(callable $operation): void
    {
        $repository = $this->getRepositoryMock();
        $repository->expects(self::once())->method('beginTransaction');
        $operation();
        $repository->expects(self::once())->method('commit');
        $repository->expects(self::never())->method('rollback');
    }

    private function assertTransactionIsNotStarted(callable $operation): void
    {
        $repository = $this->getRepositoryMock();
        $repository->expects(self::never())->method('beginTransaction');
        $operation();
        $repository->expects(self::never())->method('commit');
        $repository->expects(self::never())->method('rollback');
    }

    private function assertTransactionIsRollback(callable $operation): void
    {
        $repository = $this->getRepositoryMock();
        $repository->expects(self::once())->method('beginTransaction');
        $operation();
        $repository->expects(self::never())->method('commit');
        $repository->expects(self::once())->method('rollback');
    }

    private function createAPIUserPreference(): APIUserPreference
    {
        return new APIUserPreference([
            'name' => 'setting',
            'value' => 'value',
        ]);
    }
}
