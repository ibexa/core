<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Core\Repository\Values\ContentType;

use Ibexa\Contracts\Core\Repository\Values\ContentType\FieldDefinition;
use Ibexa\Contracts\Core\Repository\Values\ContentType\FieldDefinitionCollection as APIFieldDefinitionCollection;
use Ibexa\Core\Repository\Values\ContentType\ContentType;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Ibexa\Core\Repository\Values\ContentType\ContentType
 */
final class ContentTypeTest extends TestCase
{
    private const EXAMPLE_FIELD_DEFINITION_IDENTIFIER = 'example';
    private const EXAMPLE_FIELD_TYPE_IDENTIFIER = 'ezcustom';

    public function testStrictGetters(): void
    {
        $identifier = 'foo_content_type';
        $contentType = new ContentType(['identifier' => $identifier]);

        self::assertSame($identifier, $contentType->getIdentifier());
    }

    public function testGetFieldDefinition(): void
    {
        $fieldDefinition = $this->createMock(FieldDefinition::class);

        $fieldDefinitionCollection = $this->createMock(APIFieldDefinitionCollection::class);

        $fieldDefinitionCollection
            ->expects(self::once())
            ->method('has')
            ->with(self::EXAMPLE_FIELD_DEFINITION_IDENTIFIER)
            ->willReturn(true);

        $fieldDefinitionCollection
            ->expects(self::once())
            ->method('get')
            ->with(self::EXAMPLE_FIELD_DEFINITION_IDENTIFIER)
            ->willReturn($fieldDefinition);

        $contentType = new ContentType([
            'fieldDefinitions' => $fieldDefinitionCollection,
        ]);

        self::assertEquals(
            $fieldDefinition,
            $contentType->getFieldDefinition(self::EXAMPLE_FIELD_DEFINITION_IDENTIFIER)
        );
    }

    public function testHasFieldDefinition(): void
    {
        $fieldDefinitionCollection = $this->createMock(APIFieldDefinitionCollection::class);
        $fieldDefinitionCollection
            ->expects(self::once())
            ->method('has')
            ->with(self::EXAMPLE_FIELD_DEFINITION_IDENTIFIER)
            ->willReturn(true);

        $contentType = new ContentType([
            'fieldDefinitions' => $fieldDefinitionCollection,
        ]);

        self::assertTrue(
            $contentType->hasFieldDefinition(self::EXAMPLE_FIELD_DEFINITION_IDENTIFIER)
        );
    }

    public function testHasFieldDefinitionOfType(): void
    {
        $fieldDefinitionCollection = $this->createMock(APIFieldDefinitionCollection::class);
        $fieldDefinitionCollection
            ->expects(self::once())
            ->method('anyOfType')
            ->with(self::EXAMPLE_FIELD_TYPE_IDENTIFIER)
            ->willReturn(true);

        $contentType = new ContentType([
            'fieldDefinitions' => $fieldDefinitionCollection,
        ]);

        self::assertTrue(
            $contentType->hasFieldDefinitionOfType(self::EXAMPLE_FIELD_TYPE_IDENTIFIER)
        );
    }

    public function testGetFieldDefinitionsOfType(): void
    {
        $expectedFieldDefinitionCollection = $this->createMock(APIFieldDefinitionCollection::class);

        $fieldDefinitionCollection = $this->createMock(APIFieldDefinitionCollection::class);
        $fieldDefinitionCollection
            ->expects(self::once())
            ->method('filterByType')
            ->with(self::EXAMPLE_FIELD_TYPE_IDENTIFIER)
            ->willReturn($expectedFieldDefinitionCollection);

        $contentType = new ContentType([
            'fieldDefinitions' => $fieldDefinitionCollection,
        ]);

        self::assertEquals(
            $expectedFieldDefinitionCollection,
            $contentType->getFieldDefinitionsOfType(self::EXAMPLE_FIELD_TYPE_IDENTIFIER)
        );
    }

    public function testGetFirstFieldDefinitionOfType(): void
    {
        $expectedFieldDefinition = $this->createMock(FieldDefinition::class);

        $filteredFieldDefinitionCollection = $this->createMock(APIFieldDefinitionCollection::class);
        $filteredFieldDefinitionCollection
            ->method('first')
            ->willReturn($expectedFieldDefinition);

        $fieldDefinitionCollection = $this->createMock(APIFieldDefinitionCollection::class);
        $fieldDefinitionCollection
            ->expects(self::once())
            ->method('filterByType')
            ->with(self::EXAMPLE_FIELD_TYPE_IDENTIFIER)
            ->willReturn($filteredFieldDefinitionCollection);

        $contentType = new ContentType([
            'fieldDefinitions' => $fieldDefinitionCollection,
        ]);

        self::assertEquals(
            $expectedFieldDefinition,
            $contentType->getFirstFieldDefinitionOfType(self::EXAMPLE_FIELD_TYPE_IDENTIFIER)
        );
    }
}
