<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Core\Repository\Values\Content;

use Ibexa\Contracts\Core\Repository\Values\Content\ContentInfo;
use Ibexa\Contracts\Core\Repository\Values\Content\Field;
use Ibexa\Core\FieldType\TextLine\Value as TextLineValue;
use Ibexa\Core\Repository\Values\Content\Content;
use Ibexa\Core\Repository\Values\Content\VersionInfo;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Ibexa\Core\Repository\Values\Content\Content
 */
final class ContentTest extends TestCase
{
    /** @var \Ibexa\Contracts\Core\Repository\Values\Content\Field[] */
    private $internalFields;

    /** @var \Ibexa\Core\Repository\Values\Content\Content */
    private $content;

    protected function setUp(): void
    {
        $this->internalFields = [
            new Field(
                [
                    'fieldDefIdentifier' => 'foo',
                    'languageCode' => 'pol-PL',
                    'value' => new TextLineValue('Foo'),
                    'fieldTypeIdentifier' => 'string',
                ]
            ),
            new Field(
                [
                    'fieldDefIdentifier' => 'foo',
                    'languageCode' => 'eng-GB',
                    'value' => new TextLineValue('English Foo'),
                    'fieldTypeIdentifier' => 'string',
                ]
            ),
            new Field(
                [
                    'fieldDefIdentifier' => 'bar',
                    'languageCode' => 'pol-PL',
                    'value' => new TextLineValue('Bar'),
                    'fieldTypeIdentifier' => 'custom_type',
                ]
            ),
        ];

        $this->content = new Content(
            [
                'internalFields' => $this->internalFields,
                'prioritizedFieldLanguageCode' => 'pol-PL',
            ]
        );
    }

    public function testGetFields(): void
    {
        self::assertSame($this->internalFields, $this->content->getFields());
    }

    public function testGetField(): void
    {
        self::assertSame($this->internalFields[0], $this->content->getField('foo'));
        self::assertSame($this->internalFields[1], $this->content->getField('foo', 'eng-GB'));
    }

    public function testGetFieldValue(): void
    {
        self::assertEquals(new TextLineValue('Bar'), $this->content->getFieldValue('bar', 'pol-PL'));
        self::assertNull($this->content->getFieldValue('bar', 'eng-GB'));
    }

    public function testGetFieldsByLanguage(): void
    {
        self::assertSame(
            [
                'foo' => $this->internalFields[0],
                'bar' => $this->internalFields[2],
            ],
            $this->content->getFieldsByLanguage('pol-PL')
        );
    }

    public function testObjectProperties(): void
    {
        $object = new Content(['internalFields' => []]);
        $properties = $object->attributes();
        self::assertNotContains('internalFields', $properties, 'Internal property found ');
        self::assertContains('id', $properties, 'Property not found ');
        self::assertContains('fields', $properties, 'Property not found ');
        self::assertContains('versionInfo', $properties, 'Property not found ');
        self::assertContains('contentInfo', $properties, 'Property not found ');

        // check for duplicates and double check existence of property
        $propertiesHash = [];
        foreach ($properties as $property) {
            if (isset($propertiesHash[$property])) {
                self::fail("Property '{$property}' exists several times in properties list");
            } elseif (!isset($object->$property)) {
                self::fail("Property '{$property}' does not exist on object, even though it was hinted to be there");
            }
            $propertiesHash[$property] = 1;
        }
    }

    public function testStrictGetters(): void
    {
        $contentInfo = new ContentInfo(['id' => 123]);
        $content = new Content(
            [
                'versionInfo' => new VersionInfo(
                    [
                        'contentInfo' => $contentInfo,
                    ]
                ),
            ]
        );
        self::assertEquals(123, $content->getId());
        self::assertEquals($contentInfo, $content->getContentInfo());
    }

    public function testGetName(): void
    {
        $name = 'Translated name';
        $versionInfoMock = $this->createMock(VersionInfo::class);
        $versionInfoMock->expects(self::once())
            ->method('getName')
            ->willReturn($name);

        $object = new Content(['versionInfo' => $versionInfoMock]);

        self::assertEquals($name, $object->getName());
    }
}
