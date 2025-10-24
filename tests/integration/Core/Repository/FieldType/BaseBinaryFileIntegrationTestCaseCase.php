<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Integration\Core\Repository\FieldType;

use Ibexa\Contracts\Core\Repository\Values\Content\Field;
use Ibexa\Core\Base\Exceptions\InvalidArgumentValue;
use Ibexa\Core\FieldType\BinaryBase\Value;
use Ibexa\Core\FieldType\BinaryBase\Value as BinaryBaseValue;

/**
 * @phpstan-type TBaseBinaryFileFieldValueHash array{id: string|null, uri?: string|null, inputUri: string|null, fileName?: string, fileSize?: false|int, mimeType?: string, downloadCount?: int}
 * @phpstan-type TFixtureDataHash array{create: TBaseBinaryFileFieldValueHash, update: TBaseBinaryFileFieldValueHash}
 */
abstract class BaseBinaryFileIntegrationTestCaseCase extends FileSearchBaseIntegrationTestCase
{
    private const string FOO_BAR_SAMPLE_FILE_PATH = '/foo/bar/sindelfingen.pdf';

    /**
     * Stores the loaded file for copy test.
     */
    protected static string $loadedFilePath;

    /**
     * IOService storage prefix for the tested Type's files.
     */
    protected static string $storagePrefixConfigKey = 'ibexa.io.binary_file.storage.prefix';

    abstract public function asserFieldValueIsCorrectInstance(Field $field): void;

    /**
     * @phpstan-return TFixtureDataHash
     */
    abstract protected function getFixtureData(): array;

    abstract protected function buildBinaryFileValue(string $fileId): BinaryBaseValue;

    /**
     * @phpstan-return list<array{TBaseBinaryFileFieldValueHash, Value}>
     */
    abstract public function provideFromHashData(): array;

    /**
     * @phpstan-return list<array{Value, TBaseBinaryFileFieldValueHash}>
     */
    abstract public function provideToHashData(): array;

    /**
     * @phpstan-param TBaseBinaryFileFieldValueHash $fileFieldValueData
     */
    abstract protected function buildBinaryFileValueFromFixtureData(array $fileFieldValueData): BinaryBaseValue;

    public function assertFieldDataLoadedCorrect(Field $field): void
    {
        $this->asserFieldValueIsCorrectInstance($field);

        $fixtureData = $this->getFixtureData();
        $this->assertCreatedUpdatedBinaryFieldDataLoadedCorrectly($fixtureData['create'], $field);
    }

    public function assertUpdatedFieldDataLoadedCorrect(Field $field): void
    {
        $this->asserFieldValueIsCorrectInstance($field);

        $fixtureData = $this->getFixtureData();
        $this->assertCreatedUpdatedBinaryFieldDataLoadedCorrectly($fixtureData['update'], $field);
    }

    public function assertCopiedFieldDataLoadedCorrectly(Field $field): void
    {
        $this->assertFieldDataLoadedCorrect($field);

        self::assertEquals(
            self::$loadedFilePath,
            $field->value->id
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function getInvalidFieldSettings(): array
    {
        return [
            'somethingUnknown' => 0,
        ];
    }

    /**
     * @return array<string, array<string, array{type: string, default: mixed}>>
     */
    public function getValidatorSchema(): array
    {
        return [
            'FileSizeValidator' => [
                'maxFileSize' => [
                    'type' => 'int',
                    'default' => false,
                ],
            ],
        ];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function getValidValidatorConfiguration(): array
    {
        return [
            'FileSizeValidator' => [
                'maxFileSize' => 2 * 1024 * 1024, // 2 MB
            ],
        ];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function getInvalidValidatorConfiguration(): array
    {
        return [
            'StringLengthValidator' => [
                'minStringLength' => new \stdClass(),
            ],
        ];
    }

    public function getFieldName(): string
    {
        return 'Icy-Night-Flower-Binary.jpg';
    }

    /**
     * @return list<array{mixed, class-string}>
     */
    public function provideInvalidCreationFieldData(): array
    {
        return [
            [
                [
                    'id' => self::FOO_BAR_SAMPLE_FILE_PATH,
                ],
                InvalidArgumentValue::class,
            ],
            [
                $this->buildBinaryFileValue(self::FOO_BAR_SAMPLE_FILE_PATH),
                InvalidArgumentValue::class,
            ],
        ];
    }

    public function getValidCreationFieldData(): BinaryBaseValue
    {
        $fixtureData = $this->getFixtureData();

        return $this->buildBinaryFileValueFromFixtureData($fixtureData['create']);
    }

    public function getValidUpdateFieldData(): BinaryBaseValue
    {
        $fixtureData = $this->getFixtureData();

        return $this->buildBinaryFileValueFromFixtureData($fixtureData['update']);
    }

    /**
     * @return list<array<BinaryBaseValue>>
     */
    public function providerForTestIsNotEmptyValue(): array
    {
        return [
            [
                $this->getValidCreationFieldData(),
            ],
        ];
    }

    /**
     * @return list<array{mixed, class-string}>
     */
    public function provideInvalidUpdateFieldData(): array
    {
        return $this->provideInvalidCreationFieldData();
    }

    protected function getStoragePrefix(): string
    {
        $configValue = $this->getConfigValue(self::$storagePrefixConfigKey);
        if (!is_string($configValue)) {
            self::fail(sprintf('"%s" config key value is not a string', self::$storagePrefixConfigKey));
        }

        return $configValue;
    }

    protected function getSearchTargetValueOne(): string
    {
        $value = $this->getValidSearchValueOne();

        // ensure case-insensitivity
        return strtoupper($value->fileName);
    }

    protected function getSearchTargetValueTwo(): string
    {
        $value = $this->getValidSearchValueTwo();

        // ensure case-insensitivity
        return strtoupper($value->fileName);
    }

    /**
     * @return list<array<mixed>>
     */
    protected function getAdditionallyIndexedFieldData(): array
    {
        return [
            [
                'file_size',
                $this->getValidSearchValueOne()->fileSize,
                $this->getValidSearchValueTwo()->fileSize,
            ],
            [
                'mime_type',
                // ensure case-insensitivity
                'IMAGE/JPEG',
                'IMAGE/PNG',
            ],
        ];
    }

    /**
     * @phpstan-param TBaseBinaryFileFieldValueHash $expectedData
     */
    private function assertCreatedUpdatedBinaryFieldDataLoadedCorrectly(
        array $expectedData,
        Field $field
    ): void {
        // Will change during storage
        unset($expectedData['id']);
        $expectedData['inputUri'] = null;

        self::assertNotEmpty($field->value->id);
        self::assertInstanceOf(BinaryBaseValue::class, $field->value);
        $this->assertPropertiesCorrect(
            $expectedData,
            $field->value
        );

        self::assertTrue(
            $this->uriExistsOnIO($field->value->uri),
            "File {$field->value->uri} doesn't exist."
        );

        self::$loadedFilePath = (string)$field->value->id;
    }
}
