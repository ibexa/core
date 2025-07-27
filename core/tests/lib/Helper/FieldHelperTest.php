<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Core\Helper;

use Ibexa\Contracts\Core\Repository\FieldTypeService;
use Ibexa\Contracts\Core\Repository\Values\Content\Content as APIContent;
use Ibexa\Contracts\Core\Repository\Values\Content\ContentInfo;
use Ibexa\Contracts\Core\Repository\Values\Content\Field;
use Ibexa\Contracts\Core\Repository\Values\ContentType\ContentType;
use Ibexa\Contracts\Core\Repository\Values\ContentType\FieldDefinition;
use Ibexa\Core\FieldType\TextLine\Type as TextLineType;
use Ibexa\Core\FieldType\TextLine\Value;
use Ibexa\Core\Helper\FieldHelper;
use Ibexa\Core\Helper\TranslationHelper;
use Ibexa\Core\Repository\Values\ContentType\FieldType;
use PHPUnit\Framework\TestCase;

class FieldHelperTest extends TestCase
{
    /** @var \Ibexa\Core\Helper\FieldHelper */
    private $fieldHelper;

    /** @var \Ibexa\Contracts\Core\Repository\FieldTypeService|\PHPUnit\Framework\MockObject\MockObject */
    private $fieldTypeServiceMock;

    /** @var \Ibexa\Core\Helper\TranslationHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $translationHelper;

    protected function setUp(): void
    {
        parent::setUp();
        $this->fieldTypeServiceMock = $this->createMock(FieldTypeService::class);
        $this->translationHelper = $this->createMock(TranslationHelper::class);
        $this->fieldHelper = new FieldHelper($this->translationHelper, $this->fieldTypeServiceMock);
    }

    public function testIsFieldEmpty()
    {
        $contentTypeId = 123;
        $contentInfo = new ContentInfo(['contentTypeId' => $contentTypeId]);
        $content = $this->createMock(APIContent::class);
        $content
            ->expects(self::any())
            ->method('__get')
            ->with('contentInfo')
            ->will(self::returnValue($contentInfo));

        $fieldDefIdentifier = 'my_field_definition';
        $textLineFT = new TextLineType();
        $emptyValue = $textLineFT->getEmptyValue();
        $emptyField = new Field(['fieldDefIdentifier' => $fieldDefIdentifier, 'value' => $emptyValue]);

        $contentType = $this->createMock(ContentType::class);
        $fieldDefinition = $this->getMockBuilder(FieldDefinition::class)
            ->setConstructorArgs([['fieldTypeIdentifier' => 'ibexa_string']])
            ->getMockForAbstractClass();
        $contentType
            ->expects(self::once())
            ->method('getFieldDefinition')
            ->with($fieldDefIdentifier)
            ->will(self::returnValue($fieldDefinition));

        $content
            ->expects(self::any())
            ->method('getContentType')
            ->willReturn($contentType);

        $this->translationHelper
            ->expects(self::once())
            ->method('getTranslatedField')
            ->with($content, $fieldDefIdentifier)
            ->will(self::returnValue($emptyField));

        $this->fieldTypeServiceMock
            ->expects(self::any())
            ->method('getFieldType')
            ->with('ibexa_string')
            ->will(self::returnValue(new FieldType($textLineFT)));

        self::assertTrue($this->fieldHelper->isFieldEmpty($content, $fieldDefIdentifier));
    }

    public function testIsFieldNotEmpty()
    {
        $contentTypeId = 123;
        $contentInfo = new ContentInfo(['contentTypeId' => $contentTypeId]);
        $content = $this->createMock(APIContent::class);
        $content
            ->expects(self::any())
            ->method('__get')
            ->with('contentInfo')
            ->will(self::returnValue($contentInfo));

        $fieldDefIdentifier = 'my_field_definition';
        $textLineFT = new TextLineType();
        $nonEmptyValue = new Value('Vive le sucre !!!');
        $emptyField = new Field(['fieldDefIdentifier' => 'ibexa_string', 'value' => $nonEmptyValue]);

        $contentType = $this->createMock(ContentType::class);
        $fieldDefinition = $this->getMockBuilder(FieldDefinition::class)
            ->setConstructorArgs([['fieldTypeIdentifier' => 'ibexa_string']])
            ->getMockForAbstractClass();
        $contentType
            ->expects(self::once())
            ->method('getFieldDefinition')
            ->with($fieldDefIdentifier)
            ->will(self::returnValue($fieldDefinition));

        $content
            ->expects(self::any())
            ->method('getContentType')
            ->willReturn($contentType);

        $this->translationHelper
            ->expects(self::once())
            ->method('getTranslatedField')
            ->with($content, $fieldDefIdentifier)
            ->will(self::returnValue($emptyField));

        $this->fieldTypeServiceMock
            ->expects(self::any())
            ->method('getFieldType')
            ->with('ibexa_string')
            ->will(self::returnValue(new FieldType($textLineFT)));

        self::assertFalse($this->fieldHelper->isFieldEmpty($content, $fieldDefIdentifier));
    }
}
