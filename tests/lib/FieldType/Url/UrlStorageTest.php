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
            ->expects($this->once())
            ->method('getUrlIdMap')
            ->with(['http://ibexa.co'])
            ->will($this->returnValue(['http://ibexa.co' => 12]));

        $gateway
            ->expects($this->once())
            ->method('linkUrl')
            ->with(12, 42, 24);

        $gateway
            ->expects($this->once())
            ->method('unlinkUrl')
            ->with(
                42,
                24,
                [12]
            );

        $storage = $this->getPartlyMockedStorage($gateway);
        $result = $storage->storeFieldData($versionInfo, $field, $this->getContext());

        $this->assertTrue($result);
        $this->assertEquals(12, $field->value->data['urlId']);
    }

    public function testStoreFieldDataWithNewUrl()
    {
        $versionInfo = new VersionInfo(['versionNo' => 24]);
        $fieldValue = new FieldValue(['externalData' => 'http://ibexa.co']);
        $field = new Field(['id' => 42, 'value' => $fieldValue]);
        $gateway = $this->getGatewayMock();

        $gateway
            ->expects($this->once())
            ->method('getUrlIdMap')
            ->with(['http://ibexa.co'])
            ->will($this->returnValue([]));

        $gateway
            ->expects($this->once())
            ->method('insertUrl')
            ->with('http://ibexa.co')
            ->will($this->returnValue(12));

        $gateway
            ->expects($this->once())
            ->method('linkUrl')
            ->with(12, 42, 24);

        $gateway
            ->expects($this->once())
            ->method('unlinkUrl')
            ->with(
                42,
                24,
                [12]
            );

        $storage = $this->getPartlyMockedStorage($gateway);
        $result = $storage->storeFieldData($versionInfo, $field, $this->getContext());

        $this->assertTrue($result);
        $this->assertEquals(12, $field->value->data['urlId']);
    }

    public function testStoreFieldDataWithEmptyUrl()
    {
        $versionInfo = new VersionInfo(['versionNo' => 24]);
        $fieldValue = new FieldValue(['externalData' => '']);
        $field = new Field(['id' => 42, 'value' => $fieldValue]);
        $gateway = $this->getGatewayMock();

        $gateway
            ->expects($this->never())
            ->method('getUrlIdMap');

        $gateway
            ->expects($this->never())
            ->method('insertUrl');

        $gateway
            ->expects($this->never())
            ->method('linkUrl');

        $gateway
            ->expects($this->never())
            ->method('unlinkUrl');

        $storage = $this->getPartlyMockedStorage($gateway);
        $result = $storage->storeFieldData($versionInfo, $field, $this->getContext());

        $this->assertFalse($result);
        $this->assertNull($field->value->data);
    }

    public function testGetFieldData()
    {
        $versionInfo = new VersionInfo();
        $fieldValue = new FieldValue(['data' => ['urlId' => 12]]);
        $field = new Field(['id' => 42, 'value' => $fieldValue]);
        $gateway = $this->getGatewayMock();

        $gateway
            ->expects($this->once())
            ->method('getIdUrlMap')
            ->with([12])
            ->will($this->returnValue([12 => 'http://ibexa.co']));

        $storage = $this->getPartlyMockedStorage($gateway);
        $storage->getFieldData($versionInfo, $field, $this->getContext());

        $this->assertEquals('http://ibexa.co', $field->value->externalData);
    }

    public function testGetFieldDataNotFound()
    {
        $versionInfo = new VersionInfo();
        $fieldValue = new FieldValue(['data' => ['urlId' => 12]]);
        $field = new Field(['id' => 42, 'value' => $fieldValue]);
        $gateway = $this->getGatewayMock();

        $gateway
            ->expects($this->once())
            ->method('getIdUrlMap')
            ->with([12])
            ->will($this->returnValue([]));

        $storage = $this->getPartlyMockedStorage($gateway);
        $logger = $this->getLoggerMock();
        $logger
            ->expects($this->once())
            ->method('error')
            ->with("URL with ID '12' not found");

        $storage->getFieldData($versionInfo, $field, $this->getContext());

        $this->assertEquals('', $field->value->externalData);
    }

    public function testGetFieldDataWithEmptyUrlId()
    {
        $versionInfo = new VersionInfo();
        $fieldValue = new FieldValue(['data' => ['urlId' => null]]);
        $field = new Field(['id' => 42, 'value' => $fieldValue]);
        $gateway = $this->getGatewayMock();

        $gateway
            ->expects($this->never())
            ->method('getIdUrlMap');

        $logger = $this->getLoggerMock();
        $logger
            ->expects($this->never())
            ->method('error');

        $storage = $this->getPartlyMockedStorage($gateway);
        $storage->getFieldData($versionInfo, $field, $this->getContext());

        $this->assertNull($field->value->externalData);
    }

    public function testDeleteFieldData()
    {
        $versionInfo = new VersionInfo(['versionNo' => 24]);
        $fieldIds = [12, 23, 34];
        $gateway = $this->getGatewayMock();

        foreach ($fieldIds as $index => $id) {
            $gateway
                ->expects($this->at($index))
                ->method('unlinkUrl')
                ->with($id, 24);
        }

        $storage = $this->getPartlyMockedStorage($gateway);
        $storage->deleteFieldData($versionInfo, $fieldIds, $this->getContext());
    }

    public function testHasFieldData()
    {
        $storage = $this->getPartlyMockedStorage($this->getGatewayMock());

        $this->assertTrue($storage->hasFieldData());
    }

    /**
     * @param \Ibexa\Contracts\Core\FieldType\StorageGatewayInterface $gateway
     *
     * @return \Ibexa\Core\FieldType\Url\UrlStorage|\PHPUnit\Framework\MockObject\MockObject
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

    /**
     * @return array
     */
    protected function getContext()
    {
        return ['context'];
    }

    /** @var \Psr\Log\LoggerInterface|\PHPUnit\Framework\MockObject\MockObject */
    protected $loggerMock;

    /**
     * @return \Psr\Log\LoggerInterface|\PHPUnit\Framework\MockObject\MockObject
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

    /** @var \Ibexa\Core\FieldType\Url\UrlStorage\Gateway|\PHPUnit\Framework\MockObject\MockObject */
    protected $gatewayMock;

    /**
     * @return \Ibexa\Core\FieldType\Url\UrlStorage\Gateway|\PHPUnit\Framework\MockObject\MockObject
     */
    protected function getGatewayMock()
    {
        if (!isset($this->gatewayMock)) {
            $this->gatewayMock = $this->createMock(UrlStorage\Gateway::class);
        }

        return $this->gatewayMock;
    }
}

class_alias(UrlStorageTest::class, 'eZ\Publish\Core\FieldType\Tests\Url\UrlStorageTest');
