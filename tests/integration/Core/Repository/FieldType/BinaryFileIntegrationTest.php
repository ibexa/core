<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Integration\Core\Repository\FieldType;

use Ibexa\Contracts\Core\Repository\Values\Content\Field;
use Ibexa\Contracts\Core\Test\Repository\SetupFactory\Legacy;
use Ibexa\Core\FieldType\BinaryBase\Value as BinaryBaseValue;
use Ibexa\Core\FieldType\BinaryFile\Value as BinaryFileValue;
use PHPUnit\Framework\Attributes\Group;

/**
 * Integration test for use field type.
 */
#[Group('integration')]
#[Group('field-type')]
final class BinaryFileIntegrationTest extends BaseBinaryFileIntegrationTestCaseCase
{
    protected static function getFixtureData(): array
    {
        return [
            'create' => [
                'id' => null,
                'inputUri' => ($path = __DIR__ . '/_fixtures/image.jpg'),
                'fileName' => 'Icy-Night-Flower-Binary.jpg',
                'fileSize' => filesize($path),
                'mimeType' => 'image/jpeg',
                // Left out 'downloadCount' by intention (will be set to 0)
            ],
            'update' => [
                'id' => null,
                'inputUri' => ($path = __DIR__ . '/_fixtures/image.png'),
                'fileName' => 'Blue-Blue-Blue-Sindelfingen.png',
                'fileSize' => filesize($path),
                'downloadCount' => 23,
                // Left out 'mimeType' by intention (will be auto-detected)
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
        return 'ibexa_binaryfile';
    }

    /**
     * @return array{}
     */
    public function getSettingsSchema(): array
    {
        return [];
    }

    /**
     * @return array{}
     */
    public function getValidFieldSettings(): array
    {
        return [];
    }

    protected static function buildBinaryFileValueFromFixtureData(array $fileFieldValueData): BinaryBaseValue
    {
        return new BinaryFileValue($fileFieldValueData);
    }

    public function asserFieldValueIsCorrectInstance(Field $field): void
    {
        self::assertInstanceOf(
            BinaryFileValue::class,
            $field->value
        );
    }

    protected static function buildBinaryFileValue(string $fileId): BinaryBaseValue
    {
        return new BinaryFileValue(
            [
                'id' => $fileId,
            ]
        );
    }

    public static function provideToHashData(): array
    {
        $fixture = static::getFixtureData();
        $expected = $fixture['create'];
        $expected['downloadCount'] = 0;
        $expected['uri'] = $expected['inputUri'];
        $expected['path'] = $expected['inputUri'];

        $fieldValue = static::buildBinaryFileValueFromFixtureData($fixture['create']);
        $fieldValue->uri = $expected['uri'];

        return [
            [
                $fieldValue,
                $expected,
            ],
        ];
    }

    public static function provideFromHashData(): array
    {
        $fixture = static::getFixtureData();
        $fixture['create']['downloadCount'] = 0;
        $fixture['create']['uri'] = $fixture['create']['inputUri'];

        $fieldValue = static::buildBinaryFileValueFromFixtureData($fixture['create']);
        $fieldValue->uri = $fixture['create']['uri'];

        return [
            [
                $fixture['create'],
                $fieldValue,
            ],
        ];
    }

    /**
     * @return list<array<\Ibexa\Core\FieldType\BinaryBase\Value>>
     */
    public static function providerForTestIsEmptyValue(): array
    {
        return [
            [new BinaryFileValue()],
            [new BinaryFileValue([])],
        ];
    }

    protected static function getValidSearchValueOne(): BinaryFileValue
    {
        return new BinaryFileValue(
            [
                'inputUri' => ($path = __DIR__ . '/_fixtures/image.jpg'),
                'fileName' => 'blue-blue-blue-sindelfingen.jpg',
                'fileSize' => filesize($path),
            ]
        );
    }

    /**
     * BinaryFile field type is not searchable with Field criterion
     * and sort clause in Legacy search engine.
     *
     * @throws \ErrorException
     */
    protected function checkSearchEngineSupport(): void
    {
        if ($this->getSetupFactory() instanceof Legacy) {
            self::markTestSkipped(
                "'ibexa_binaryfile' field type is not searchable with Legacy Search Engine"
            );
        }
    }

    protected static function getValidSearchValueTwo(): BinaryFileValue
    {
        return new BinaryFileValue(
            [
                'inputUri' => ($path = __DIR__ . '/_fixtures/image.png'),
                'fileName' => 'icy-night-flower-binary.png',
                'fileSize' => filesize($path),
            ]
        );
    }
}
