<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Integration\Core\Repository\FieldType;

use Ibexa\Contracts\Core\Repository\Values\Content\Field;
use Ibexa\Core\FieldType\BinaryBase\Value;
use Ibexa\Core\FieldType\BinaryBase\Value as BinaryBaseValue;
use Ibexa\Core\FieldType\Media\Type as MediaType;
use Ibexa\Core\FieldType\Media\Value as MediaValue;

/**
 * Integration test for use field type.
 *
 * @group integration
 * @group field-type
 */
final class MediaIntegrationTest extends BaseBinaryFileIntegrationTestCaseCase
{
    protected function getFixtureData(): array
    {
        return [
            'create' => [
                'id' => null,
                'inputUri' => ($path = __DIR__ . '/_fixtures/image.jpg'),
                'fileName' => 'Icy-Night-Flower-Binary.jpg',
                'fileSize' => filesize($path),
                'mimeType' => 'image/jpeg',
                // Left out 'hasControlls', 'autoplay', 'loop', 'height' and
                // 'width' by intention (will be set to defaults)
            ],
            'update' => [
                'id' => null,
                'inputUri' => ($path = __DIR__ . '/_fixtures/image.png'),
                'fileName' => 'Blue-Blue-Blue-Sindelfingen.png',
                'fileSize' => filesize($path),
                // Left out 'mimeType' by intention (will be auto-detected)
                'hasController' => true,
                'autoplay' => true,
                'loop' => true,
                'width' => 23,
                'height' => 42,
            ],
        ];
    }

    /**
     * Get name of tested field type.
     *
     * @return string
     */
    public function getTypeName(): string
    {
        return 'ibexa_media';
    }

    /**
     * @return array<string, array{type: string, default: mixed}>
     */
    public function getSettingsSchema(): array
    {
        return [
            'mediaType' => [
                'type' => 'choice',
                'default' => MediaType::TYPE_HTML5_VIDEO,
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function getValidFieldSettings(): array
    {
        return [
            'mediaType' => MediaType::TYPE_FLASH,
        ];
    }

    protected function buildBinaryFileValueFromFixtureData(array $fileFieldValueData): BinaryBaseValue
    {
        return new MediaValue($fileFieldValueData);
    }

    public function asserFieldValueIsCorrectInstance(Field $field): void
    {
        self::assertInstanceOf(
            MediaValue::class,
            $field->value
        );
    }

    protected function buildBinaryFileValue(string $fileId): BinaryBaseValue
    {
        return new MediaValue(
            [
                'id' => $fileId,
            ]
        );
    }

    public function provideToHashData(): array
    {
        $fixture = $this->getFixtureData();
        $expected = $fixture['create'];

        $expected['uri'] = $expected['inputUri'];
        $expected['path'] = $expected['inputUri'];

        // Defaults set by type
        $expected['hasController'] = false;
        $expected['autoplay'] = false;
        $expected['loop'] = false;
        $expected['width'] = 0;
        $expected['height'] = 0;

        $fieldValue = $this->getValidCreationFieldData();
        $fieldValue->uri = $expected['uri'];

        return [
            [
                $fieldValue,
                $expected,
            ],
        ];
    }

    public function provideFromHashData(): array
    {
        $fixture = $this->getFixtureData();
        $fixture['create']['uri'] = $fixture['create']['id'];

        $fieldValue = $this->getValidCreationFieldData();
        $fieldValue->uri = $fixture['create']['uri'];

        return [
            [
                $fixture['create'],
                $fieldValue,
            ],
        ];
    }

    /**
     * @return list<array<Value>>
     */
    public function providerForTestIsEmptyValue(): array
    {
        return [
            [new MediaValue()],
        ];
    }

    protected function getValidSearchValueOne(): MediaValue
    {
        return new MediaValue(
            [
                'inputUri' => ($path = __DIR__ . '/_fixtures/image.jpg'),
                'fileName' => 'blue-blue-blue-sindelfingen.jpg',
                'fileSize' => filesize($path),
            ]
        );
    }

    protected function getValidSearchValueTwo(): MediaValue
    {
        return new MediaValue(
            [
                'inputUri' => ($path = __DIR__ . '/_fixtures/image.png'),
                'fileName' => 'icy-night-flower-binary.png',
                'fileSize' => filesize($path),
            ]
        );
    }
}
