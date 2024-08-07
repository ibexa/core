<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Core\Repository\Values\ContentType;

use Ibexa\Core\FieldType\Value as BaseFieldValue;
use Ibexa\Core\Repository\Values\ContentType\FieldDefinition;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Ibexa\Core\Repository\Values\ContentType\FieldDefinition
 */
final class FieldDefinitionTest extends TestCase
{
    public function testStrictGetters(): void
    {
        $defaultValueMock = $this->createMock(BaseFieldValue::class);
        $fieldDefinition = new FieldDefinition(
            [
                'id' => 123,
                'identifier' => 'my_field_definition',
                'fieldTypeIdentifier' => 'ibexa_field_type',
                'fieldGroup' => 'content',
                'position' => 1,
                'isTranslatable' => true,
                'isRequired' => true,
                'isInfoCollector' => false,
                'defaultValue' => $defaultValueMock,
                'isSearchable' => true,
                'mainLanguageCode' => 'eng-GB',
                'isThumbnail' => true,
            ]
        );

        self::assertSame(123, $fieldDefinition->getId());
        self::assertSame('my_field_definition', $fieldDefinition->getIdentifier());
        self::assertSame('ibexa_field_type', $fieldDefinition->getFieldTypeIdentifier());
        self::assertSame('content', $fieldDefinition->getFieldGroup());
        self::assertSame(1, $fieldDefinition->getPosition());
        self::assertTrue($fieldDefinition->isTranslatable());
        self::assertTrue($fieldDefinition->isRequired());
        self::assertFalse($fieldDefinition->isInfoCollector());
        self::assertSame($defaultValueMock, $fieldDefinition->getDefaultValue());
        self::assertTrue($fieldDefinition->isSearchable());
        self::assertSame('eng-GB', $fieldDefinition->getMainLanguageCode());
        self::assertTrue($fieldDefinition->isThumbnail());
    }
}
