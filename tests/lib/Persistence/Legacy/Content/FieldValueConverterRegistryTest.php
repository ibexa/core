<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
namespace Ibexa\Tests\Core\Persistence\Legacy\Content;

use Ibexa\Tests\Core\Persistence\Legacy\TestCase;
use Ibexa\Core\Persistence\Legacy\Content\FieldValue\ConverterRegistry as Registry;
use Ibexa\Core\Persistence\Legacy\Content\FieldValue\Converter;

/**
 * Test case for FieldValue Converter Registry.
 */
class FieldValueConverterRegistryTest extends TestCase
{
    private const TYPE_NAME = 'some-type';

    /**
     * @covers \eZ\Publish\Core\Persistence\Legacy\Content\FieldValue\ConverterRegistry::register
     */
    public function testRegister()
    {
        $converter = $this->getFieldValueConverterMock();
        $registry = new Registry([self::TYPE_NAME => $converter]);

        $this->assertSame($converter, $registry->getConverter(self::TYPE_NAME));
    }

    /**
     * @covers \eZ\Publish\Core\Persistence\Legacy\Content\FieldValue\ConverterRegistry::getConverter
     */
    public function testGetStorage()
    {
        $converter = $this->getFieldValueConverterMock();
        $registry = new Registry([self::TYPE_NAME => $converter]);

        $res = $registry->getConverter(self::TYPE_NAME);

        $this->assertSame(
            $converter,
            $res
        );
    }

    /**
     * @covers \eZ\Publish\Core\Persistence\Legacy\Content\FieldValue\ConverterRegistry::getConverter
     * @covers \eZ\Publish\Core\Persistence\Legacy\Content\FieldValue\Converter\Exception\NotFound
     */
    public function testGetNotFound()
    {
        $this->expectException(\eZ\Publish\Core\Persistence\Legacy\Content\FieldValue\Converter\Exception\NotFound::class);

        $registry = new Registry([]);

        $registry->getConverter('not-found');
    }

    /**
     * @return \Ibexa\Core\Persistence\Legacy\Content\FieldValue\Converter
     */
    protected function getFieldValueConverterMock()
    {
        return $this->createMock(Converter::class);
    }
}

class_alias(FieldValueConverterRegistryTest::class, 'eZ\Publish\Core\Persistence\Legacy\Tests\Content\FieldValueConverterRegistryTest');
