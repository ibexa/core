<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Core\FieldType\Url;

use Ibexa\Contracts\Core\FieldType\StorageGatewayInterface;
use Ibexa\Contracts\Core\Persistence\Content\Field;
use Ibexa\Contracts\Core\Persistence\Content\FieldValue;
use Ibexa\Contracts\Core\Persistence\Content\VersionInfo;
use Ibexa\Core\FieldType\Url\UrlStorage;
use Ibexa\Core\FieldType\Url\UrlStorage\Gateway;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class UrlStorageTest extends TestCase
{
    public function testStoreFieldDataWithExistingUrl()
    {
        $versionInfo = new VersionInfo(['versionNo' => 24]);
        $fieldValue = new FieldValue(['externalData' => 'http://ibexa.co']);
        $field = new Field(['id' => 42, 'value' => $fieldValue]);
        $gateway = $this->getGatewayMock();

        $gateway
            ->expects(self::once())
            ->method('getUrlIdMap')
            ->with(['http://ibexa.co'])
            ->will(self::returnValue(['http://ibexa.co' => 12]));

        $gateway
            ->expects(self::once())
            ->method('linkUrl')
            ->with(12, 42, 24);

        $gateway
            ->expects(self::once())
            ->method('unlinkUrl')
            ->with(
                42,
                24,
                [12]
            );

        $storage = $this->getPartlyMockedStorage($gateway);
        $result = $storage->storeFieldData($versionInfo, $field);

        self::assertTrue($result);
        self::assertEquals(12, $field->value->data['urlId']);
    }

    public function testStoreFieldDataWithNewUrl()
    {
        $versionInfo = new VersionInfo(['versionNo' => 24]);
        $fieldValue = new FieldValue(['externalData' => 'http://ibexa.co']);
        $field = new Field(['id' => 42, 'value' => $fieldValue]);
        $gateway = $this->getGatewayMock();

        $gateway
            ->expects(self::once())
            ->method('getUrlIdMap')
            ->with(['http://ibexa.co'])
            ->will(self::returnValue([]));

        $gateway
            ->expects(self::once())
            ->method('insertUrl')
            ->with('http://ibexa.co')
            ->will(self::returnValue(12));

        $gateway
            ->expects(self::once())
            ->method('linkUrl')
            ->with(12, 42, 24);

        $gateway
            ->expects(self::once())
            ->method('unlinkUrl')
            ->with(
                42,
                24,
                [12]
            );

        $storage = $this->getPartlyMockedStorage($gateway);
        $result = $storage->storeFieldData($versionInfo, $field);

        self::assertTrue($result);
        self::assertEquals(12, $field->value->data['urlId']);
    }

    public function testStoreFieldDataWithEmptyUrl()
    {
        $versionInfo = new VersionInfo(['versionNo' => 24]);
        $fieldValue = new FieldValue(['externalData' => '']);
        $field = new Field(['id' => 42, 'value' => $fieldValue]);
        $gateway = $this->getGatewayMock();

        $gateway
            ->expects(self::never())
            ->method('getUrlIdMap');

        $gateway
            ->expects(self::never())
            ->method('insertUrl');

        $gateway
            ->expects(self::never())
            ->method('linkUrl');

        $gateway
            ->expects(self::never())
            ->method('unlinkUrl');

        $storage = $this->getPartlyMockedStorage($gateway);
        $result = $storage->storeFieldData($versionInfo, $field);

        self::assertFalse($result);
        self::assertNull($field->value->data);
    }

    public function testGetFieldData()
    {
        $versionInfo = new VersionInfo();
        $fieldValue = new FieldValue(['data' => ['urlId' => 12]]);
        $field = new Field(['id' => 42, 'value' => $fieldValue]);
        $gateway = $this->getGatewayMock();

        $gateway
            ->expects(self::once())
            ->method('getIdUrlMap')
            ->with([12])
            ->will(self::returnValue([12 => 'http://ibexa.co']));

        $storage = $this->getPartlyMockedStorage($gateway);
        $storage->getFieldData($versionInfo, $field);

        self::assertEquals('http://ibexa.co', $field->value->externalData);
    }

    public function testGetFieldDataNotFound()
    {
        $versionInfo = new VersionInfo();
        $fieldValue = new FieldValue(['data' => ['urlId' => 12]]);
        $field = new Field(['id' => 42, 'value' => $fieldValue]);
        $gateway = $this->getGatewayMock();

        $gateway
            ->expects(self::once())
            ->method('getIdUrlMap')
            ->with([12])
            ->will(self::returnValue([]));

        $storage = $this->getPartlyMockedStorage($gateway);
        $logger = $this->getLoggerMock();
        $logger
            ->expects(self::once())
            ->method('error')
            ->with("URL with ID '12' not found");

        $storage->getFieldData($versionInfo, $field);

        self::assertEquals('', $field->value->externalData);
    }

    public function testGetFieldDataWithEmptyUrlId()
    {
        $versionInfo = new VersionInfo();
        $fieldValue = new FieldValue(['data' => ['urlId' => null]]);
        $field = new Field(['id' => 42, 'value' => $fieldValue]);
        $gateway = $this->getGatewayMock();

        $gateway
            ->expects(self::never())
            ->method('getIdUrlMap');

        $logger = $this->getLoggerMock();
        $logger
            ->expects(self::never())
            ->method('error');

        $storage = $this->getPartlyMockedStorage($gateway);
        $storage->getFieldData($versionInfo, $field);

        self::assertNull($field->value->externalData);
    }

    public function testDeleteFieldData()
    {
        $versionInfo = new VersionInfo(['versionNo' => 24]);
        $fieldIds = [12, 23, 34];
        $gateway = $this->getGatewayMock();

        foreach ($fieldIds as $index => $id) {
            $gateway
                ->expects(self::at($index))
                ->method('unlinkUrl')
                ->with($id, 24);
        }

        $storage = $this->getPartlyMockedStorage($gateway);
        $storage->deleteFieldData($versionInfo, $fieldIds);
    }

    public function testHasFieldData()
    {
        $storage = $this->getPartlyMockedStorage($this->getGatewayMock());

        self::assertTrue($storage->hasFieldData());
    }

    /**
     * @param StorageGatewayInterface $gateway
     *
     * @return UrlStorage|MockObject
     */
    protected function getPartlyMockedStorage(StorageGatewayInterface $gateway)
    {
        return $this->getMockBuilder(UrlStorage::class)
            ->setMethods(null)
            ->setConstructorArgs(
                [
                    $gateway,
                    $this->getLoggerMock(),
                ]
            )
            ->getMock();
    }

    /** @var LoggerInterface|MockObject */
    protected $loggerMock;

    /**
     * @return LoggerInterface|MockObject
     */
    protected function getLoggerMock()
    {
        if (!isset($this->loggerMock)) {
            $this->loggerMock = $this->getMockForAbstractClass(
                LoggerInterface::class
            );
        }

        return $this->loggerMock;
    }

    /** @var Gateway|MockObject */
    protected $gatewayMock;

    /**
     * @return Gateway|MockObject
     */
    protected function getGatewayMock()
    {
        if (!isset($this->gatewayMock)) {
            $this->gatewayMock = $this->createMock(Gateway::class);
        }

        return $this->gatewayMock;
    }
}
