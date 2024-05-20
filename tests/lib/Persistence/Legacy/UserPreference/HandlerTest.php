<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Core\Persistence\Legacy\UserPreference;

use Ibexa\Contracts\Core\Persistence\UserPreference\UserPreference;
use Ibexa\Contracts\Core\Persistence\UserPreference\UserPreferenceSetStruct;
use Ibexa\Core\Persistence\Legacy\UserPreference\Gateway;
use Ibexa\Core\Persistence\Legacy\UserPreference\Handler;
use Ibexa\Core\Persistence\Legacy\UserPreference\Mapper;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Ibexa\Core\Persistence\Legacy\UserPreference\Handler
 */
class HandlerTest extends TestCase
{
    public const USER_PREFERENCE_ID = 1;

    /** @var \Ibexa\Core\Persistence\Legacy\UserPreference\Gateway|\PHPUnit\Framework\MockObject\MockObject */
    private $gateway;

    /** @var \Ibexa\Core\Persistence\Legacy\UserPreference\Mapper|\PHPUnit\Framework\MockObject\MockObject */
    private $mapper;

    /** @var \Ibexa\Core\Persistence\Legacy\UserPreference\Handler */
    private $handler;

    protected function setUp(): void
    {
        $this->gateway = $this->createMock(Gateway::class);
        $this->mapper = $this->createMock(Mapper::class);
        $this->handler = new Handler($this->gateway, $this->mapper);
    }

    public function testSetUserPreference()
    {
        $setStruct = new UserPreferenceSetStruct([
            'userId' => 5,
            'name' => 'setting',
            'value' => 'value',
        ]);

        $this->gateway
            ->expects(self::once())
            ->method('setUserPreference')
            ->with($setStruct)
            ->willReturn(self::USER_PREFERENCE_ID);

        $this->mapper
            ->expects(self::once())
            ->method('extractUserPreferencesFromRows')
            ->willReturn([new UserPreference([
                'id' => self::USER_PREFERENCE_ID,
            ])]);

        $userPreference = $this->handler->setUserPreference($setStruct);

        self::assertEquals($userPreference->id, self::USER_PREFERENCE_ID);
    }

    public function testCountUserPreferences()
    {
        $ownerId = 10;
        $expectedCount = 12;

        $this->gateway
            ->expects(self::once())
            ->method('countUserPreferences')
            ->with($ownerId)
            ->willReturn($expectedCount);

        self::assertEquals($expectedCount, $this->handler->countUserPreferences($ownerId));
    }

    public function testLoadUserPreferences()
    {
        $ownerId = 9;
        $limit = 5;
        $offset = 0;

        $rows = [
            ['id' => 1/* ... */],
            ['id' => 2/* ... */],
            ['id' => 3/* ... */],
        ];

        $objects = [
            new UserPreference(['id' => 1/* ... */]),
            new UserPreference(['id' => 2/* ... */]),
            new UserPreference(['id' => 3/* ... */]),
        ];

        $this->gateway
            ->expects(self::once())
            ->method('loadUserPreferences')
            ->with($ownerId, $offset, $limit)
            ->willReturn($rows);

        $this->mapper
            ->expects(self::once())
            ->method('extractUserPreferencesFromRows')
            ->with($rows)
            ->willReturn($objects);

        self::assertEquals($objects, $this->handler->loadUserPreferences($ownerId, $offset, $limit));
    }
}
