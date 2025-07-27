<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Core\Persistence\Legacy\Setting;

use Ibexa\Contracts\Core\Persistence\Setting\Setting;
use Ibexa\Core\Base\Exceptions\NotFoundException;
use Ibexa\Core\Persistence\Legacy\Setting\Gateway;
use Ibexa\Core\Persistence\Legacy\Setting\Handler;
use Ibexa\Tests\Core\Persistence\Legacy\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * @covers \Ibexa\Core\Persistence\Legacy\Setting\Handler::create
 */
final class SettingHandlerTest extends TestCase
{
    /** @var \Ibexa\Core\Persistence\Legacy\Setting\Handler */
    private $settingHandler;

    /** @var \Ibexa\Core\Persistence\Legacy\Setting\Gateway */
    private $gatewayMock;

    public function testCreate(): void
    {
        $handler = $this->getSettingHandler();
        $gatewayMock = $this->getGatewayMock();

        $gatewayMock->expects(self::once())
            ->method('insertSetting')
            ->with(
                self::identicalTo('group_a1'),
                self::identicalTo('identifier_b2'),
                self::identicalTo('value_c3')
            )->willReturn(123);

        $gatewayMock->expects(self::once())
            ->method('loadSettingById')
            ->with(
                self::identicalTo(123)
            )
            ->willReturn([
                'group' => 'group_a1',
                'identifier' => 'identifier_b2',
                'value' => 'value_c3',
            ]);

        $settingRef = new Setting([
            'group' => 'group_a1',
            'identifier' => 'identifier_b2',
            'serializedValue' => 'value_c3',
        ]);

        $result = $handler->create(
            'group_a1',
            'identifier_b2',
            'value_c3',
        );

        self::assertEquals(
            $settingRef,
            $result
        );
    }

    public function testCreateFailsToLoad(): void
    {
        $handler = $this->getSettingHandler();
        $gatewayMock = $this->getGatewayMock();

        $gatewayMock->expects(self::once())
            ->method('insertSetting')
            ->with(
                self::identicalTo('group_a1'),
                self::identicalTo('identifier_b2'),
                self::identicalTo('value_c3')
            )->willReturn(123);

        $gatewayMock->expects(self::once())
            ->method('loadSettingById')
            ->with(
                self::identicalTo(123)
            )
            ->willReturn(null);

        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage(<<<ERROR
        Could not find 'Setting' with identifier 'array (
          'group' => 'group_a1',
          'identifier' => 'identifier_b2',
        )'
        ERROR);

        $handler->create(
            'group_a1',
            'identifier_b2',
            'value_c3',
        );
    }

    /**
     * @throws \Ibexa\Core\Base\Exceptions\NotFoundException
     */
    public function testUpdate(): void
    {
        $handler = $this->getSettingHandler();
        $gatewayMock = $this->getGatewayMock();

        $gatewayMock->expects(self::once())
            ->method('updateSetting')
            ->with(
                self::identicalTo('group_d1'),
                self::identicalTo('identifier_e2'),
                self::identicalTo('value_f3')
            );

        $gatewayMock->expects(self::once())
            ->method('loadSetting')
            ->with(
                self::identicalTo('group_d1'),
                self::identicalTo('identifier_e2')
            )
            ->will(self::returnValue([
                'group' => 'group_d1',
                'identifier' => 'identifier_e2',
                'value' => 'value_f3',
            ]));

        $settingRef = new Setting([
            'group' => 'group_d1',
            'identifier' => 'identifier_e2',
            'serializedValue' => 'value_f3',
        ]);

        $result = $handler->update(
            'group_d1',
            'identifier_e2',
            'value_f3'
        );

        self::assertEquals(
            $settingRef,
            $result
        );
    }

    public function testUpdateFailsToLoad(): void
    {
        $handler = $this->getSettingHandler();
        $gatewayMock = $this->getGatewayMock();

        $gatewayMock->expects(self::once())
            ->method('updateSetting')
            ->with(
                self::identicalTo('group_d1'),
                self::identicalTo('identifier_e2'),
                self::identicalTo('value_f3')
            );

        $gatewayMock->expects(self::once())
            ->method('loadSetting')
            ->with(
                self::identicalTo('group_d1'),
                self::identicalTo('identifier_e2')
            )
            ->will(self::returnValue(null));

        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage(<<<ERROR
        Could not find 'Setting' with identifier 'array (
          'group' => 'group_d1',
          'identifier' => 'identifier_e2',
        )'
        ERROR);

        $handler->update(
            'group_d1',
            'identifier_e2',
            'value_f3'
        );
    }

    /**
     * @throws \Ibexa\Core\Base\Exceptions\NotFoundException
     */
    public function testLoad(): void
    {
        $handler = $this->getSettingHandler();

        $gatewayMock = $this->getGatewayMock();

        $gatewayMock->expects(self::once())
            ->method('loadSetting')
            ->with(
                self::identicalTo('group_a1'),
                self::identicalTo('identifier_b2')
            )->willReturn([
                'group' => 'group_a1',
                'identifier' => 'identifier_b2',
                'value' => 'value_c3',
            ]);

        $settingRef = new Setting([
            'group' => 'group_a1',
            'identifier' => 'identifier_b2',
            'serializedValue' => 'value_c3',
        ]);

        $result = $handler->load(
            'group_a1',
            'identifier_b2'
        );

        self::assertEquals(
            $settingRef,
            $result
        );
    }

    /**
     * @throws \Ibexa\Core\Base\Exceptions\NotFoundException
     */
    public function testLoadFailsToLoad(): void
    {
        $handler = $this->getSettingHandler();

        $gatewayMock = $this->getGatewayMock();

        $gatewayMock->expects(self::once())
            ->method('loadSetting')
            ->with(
                self::identicalTo('group_a1'),
                self::identicalTo('identifier_b2')
            )->willReturn(null);

        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage(<<<ERROR
        Could not find 'Setting' with identifier 'array (
          'group' => 'group_a1',
          'identifier' => 'identifier_b2',
        )'
        ERROR);

        $handler->load(
            'group_a1',
            'identifier_b2'
        );
    }

    public function testDelete(): void
    {
        $handler = $this->getSettingHandler();
        $gatewayMock = $this->getGatewayMock();

        $gatewayMock->expects(self::once())
            ->method('deleteSetting')
            ->with(
                self::identicalTo('group_a1'),
                self::identicalTo('identifier_b2')
            );

        $handler->delete(
            'group_a1',
            'identifier_b2'
        );
    }

    protected function getSettingHandler(): Handler
    {
        if (!isset($this->settingHandler)) {
            $this->settingHandler = new Handler(
                $this->getGatewayMock()
            );
        }

        return $this->settingHandler;
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|\Ibexa\Core\Persistence\Legacy\Setting\Gateway
     */
    protected function getGatewayMock(): MockObject
    {
        if (!isset($this->gatewayMock)) {
            $this->gatewayMock = $this->createMock(Gateway::class);
        }

        return $this->gatewayMock;
    }
}
