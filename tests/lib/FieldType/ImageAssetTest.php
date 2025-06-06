<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Core\FieldType;

use Ibexa\Contracts\Core\FieldType\Value as SPIValue;
use Ibexa\Contracts\Core\Persistence\Content\Handler as PersistenceContentHandler;
use Ibexa\Contracts\Core\Persistence\Content\VersionInfo;
use Ibexa\Contracts\Core\Repository\ContentService;
use Ibexa\Contracts\Core\Repository\Values\Content\Content;
use Ibexa\Contracts\Core\Repository\Values\Content\ContentInfo;
use Ibexa\Contracts\Core\Repository\Values\Content\RelationType;
use Ibexa\Contracts\Core\Repository\Values\ContentType\ContentType;
use Ibexa\Contracts\Core\Repository\Values\ContentType\FieldDefinition;
use Ibexa\Core\Base\Exceptions\InvalidArgumentException;
use Ibexa\Core\FieldType\Image\Value;
use Ibexa\Core\FieldType\ImageAsset;
use Ibexa\Core\FieldType\ValidationError;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * @group fieldType
 * @group ibexa_image_asset
 */
class ImageAssetTest extends FieldTypeTestCase
{
    private const int DESTINATION_CONTENT_ID = 14;

    private ContentService & MockObject $contentServiceMock;

    private ImageAsset\AssetMapper & MockObject $assetMapperMock;

    private PersistenceContentHandler & MockObject $contentHandlerMock;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->contentServiceMock = $this->createMock(ContentService::class);
        $this->assetMapperMock = $this->createMock(ImageAsset\AssetMapper::class);
        $this->contentHandlerMock = $this->createMock(PersistenceContentHandler::class);
        $versionInfo = new VersionInfo([
            'versionNo' => 24,
            'names' => [
                'en_GB' => 'name_en_GB',
                'de_DE' => 'Name_de_DE',
            ],
        ]);
        $currentVersionNo = 28;
        $destinationContentInfo = $this->createMock(ContentInfo::class);
        $destinationContentInfo
            ->method('__get')
            ->willReturnMap([
                ['currentVersionNo', $currentVersionNo],
                ['mainLanguageCode', 'en_GB'],
            ]);

        $this->contentHandlerMock
            ->method('loadContentInfo')
            ->with(self::DESTINATION_CONTENT_ID)
            ->willReturn($destinationContentInfo);

        $this->contentHandlerMock
            ->method('loadVersionInfo')
            ->with(self::DESTINATION_CONTENT_ID, $currentVersionNo)
            ->willReturn($versionInfo);
    }

    protected function provideFieldTypeIdentifier(): string
    {
        return ImageAsset\Type::FIELD_TYPE_IDENTIFIER;
    }

    protected function createFieldTypeUnderTest(): ImageAsset\Type
    {
        return new ImageAsset\Type(
            $this->contentServiceMock,
            $this->assetMapperMock,
            $this->contentHandlerMock
        );
    }

    protected function getValidatorConfigurationSchemaExpectation(): array
    {
        return [];
    }

    protected function getSettingsSchemaExpectation(): array
    {
        return [];
    }

    protected function getEmptyValueExpectation(): ImageAsset\Value
    {
        return new ImageAsset\Value();
    }

    public function provideInvalidInputForAcceptValue(): iterable
    {
        return [
            [
                true,
                InvalidArgumentException::class,
            ],
        ];
    }

    public function provideValidInputForAcceptValue(): iterable
    {
        $destinationContentId = 7;

        yield 'null input' => [
            null,
            $this->getEmptyValueExpectation(),
        ];

        yield 'content id' => [
            $destinationContentId,
            new ImageAsset\Value($destinationContentId),
        ];

        yield 'content info object' => [
            new ContentInfo([
                'id' => $destinationContentId,
            ]),
            new ImageAsset\Value($destinationContentId),
        ];
    }

    public function provideInputForToHash(): iterable
    {
        $destinationContentId = 7;
        $alternativeText = 'The alternative text for image';

        return [
            [
                new ImageAsset\Value(),
                [
                    'destinationContentId' => null,
                    'alternativeText' => null,
                ],
            ],
            [
                new ImageAsset\Value($destinationContentId),
                [
                    'destinationContentId' => $destinationContentId,
                    'alternativeText' => null,
                ],
            ],
            [
                new ImageAsset\Value($destinationContentId, $alternativeText),
                [
                    'destinationContentId' => $destinationContentId,
                    'alternativeText' => $alternativeText,
                ],
            ],
        ];
    }

    public function provideInputForFromHash(): iterable
    {
        $destinationContentId = 7;
        $alternativeText = 'The alternative text for image';

        return [
            [
                null,
                new ImageAsset\Value(),
            ],
            [
                [
                    'destinationContentId' => $destinationContentId,
                    'alternativeText' => null,
                ],
                new ImageAsset\Value($destinationContentId),
            ],
            [
                [
                    'destinationContentId' => $destinationContentId,
                    'alternativeText' => $alternativeText,
                ],
                new ImageAsset\Value($destinationContentId, $alternativeText),
            ],
        ];
    }

    public function provideInvalidDataForValidate(): iterable
    {
        yield from [];
    }

    public function testValidateNonAsset(): void
    {
        $destinationContentId = 7;
        $destinationContent = $this->createMock(Content::class);
        $invalidContentTypeIdentifier = 'article';
        $invalidContentType = $this->createMock(ContentType::class);
        $invalidContentType
            ->expects(self::once())
            ->method('__get')
            ->with('identifier')
            ->willReturn($invalidContentTypeIdentifier);

        $destinationContent
            ->method('getContentType')
            ->willReturn($invalidContentType);

        $this->contentServiceMock
            ->expects(self::once())
            ->method('loadContent')
            ->with($destinationContentId)
            ->willReturn($destinationContent);

        $this->assetMapperMock
            ->expects(self::once())
            ->method('isAsset')
            ->with($destinationContent)
            ->willReturn(false);

        $validationErrors = $this->doValidate([], new ImageAsset\Value($destinationContentId));

        self::assertIsArray($validationErrors);
        self::assertEquals([
            new ValidationError(
                'Content %type% is not a valid asset target',
                null,
                [
                    '%type%' => $invalidContentTypeIdentifier,
                ],
                'destinationContentId'
            ),
        ], $validationErrors);
    }

    public function provideValidDataForValidate(): iterable
    {
        yield 'empty value' => [
            [],
            $this->getEmptyValueExpectation(),
        ];
    }

    /**
     * @dataProvider provideDataForTestValidateValidNonEmptyAssetValue
     *
     * @param array<\Ibexa\Core\FieldType\ValidationError> $expectedValidationErrors
     */
    public function testValidateValidNonEmptyAssetValue(
        int $fileSize,
        array $expectedValidationErrors
    ): void {
        $destinationContentId = 7;
        $destinationContent = $this->createMock(Content::class);

        $this->contentServiceMock
            ->expects(self::once())
            ->method('loadContent')
            ->with($destinationContentId)
            ->willReturn($destinationContent);

        $this->assetMapperMock
            ->expects(self::once())
            ->method('isAsset')
            ->with($destinationContent)
            ->willReturn(true);

        $assetValueMock = $this->createMock(Value::class);
        $assetValueMock
            ->method('getFileSize')
            ->willReturn($fileSize);

        $this->assetMapperMock
            ->expects(self::once())
            ->method('getAssetValue')
            ->with($destinationContent)
            ->willReturn($assetValueMock);

        $fieldDefinitionMock = $this->createMock(FieldDefinition::class);
        $fieldDefinitionMock
            ->method('getValidatorConfiguration')
            ->willReturn(
                [
                    'FileSizeValidator' => [
                        'maxFileSize' => 1.4,
                    ],
                ]
            );

        $this->assetMapperMock
            ->method('getAssetFieldDefinition')
            ->willReturn($fieldDefinitionMock);

        $validationErrors = $this->doValidate([], new ImageAsset\Value($destinationContentId));
        self::assertEquals(
            $expectedValidationErrors,
            $validationErrors
        );
    }

    /**
     * @return iterable<array{
     *     int,
     *     array<\Ibexa\Core\FieldType\ValidationError>,
     * }>
     */
    public function provideDataForTestValidateValidNonEmptyAssetValue(): iterable
    {
        yield 'No validation errors' => [
            123456,
            [],
        ];

        yield 'Maximum file size exceeded' => [
            12345678912356,
            [
                new ValidationError(
                    'The file size cannot exceed %size% megabyte.',
                    'The file size cannot exceed %size% megabytes.',
                    [
                        '%size%' => 1.4,
                    ],
                    'fileSize'
                ),
            ],
        ];
    }

    public function provideDataForGetName(): array
    {
        return [
            'empty_destination_content_id' => [
                $this->getEmptyValueExpectation(),
                '',
                [],
                'en_GB',
            ],
            'destination_content_id' => [
                new ImageAsset\Value(self::DESTINATION_CONTENT_ID), 'name_en_GB', [], 'en_GB',
            ],
            'destination_content_id_de_DE' => [
                new ImageAsset\Value(self::DESTINATION_CONTENT_ID), 'Name_de_DE', [], 'de_DE',
            ],
        ];
    }

    /**
     * @dataProvider provideDataForGetName
     */
    public function testGetName(
        SPIValue $value,
        string $expected,
        array $fieldSettings = [],
        string $languageCode = 'en_GB'
    ): void {
        /** @var \Ibexa\Contracts\Core\Repository\Values\ContentType\FieldDefinition|\PHPUnit\Framework\MockObject\MockObject $fieldDefinitionMock */
        $fieldDefinitionMock = $this->createMock(FieldDefinition::class);
        $fieldDefinitionMock->method('getFieldSettings')->willReturn($fieldSettings);

        $name = $this->getFieldTypeUnderTest()->getName($value, $fieldDefinitionMock, $languageCode);

        self::assertSame($expected, $name);
    }

    public function testIsSearchable(): void
    {
        self::assertTrue($this->getFieldTypeUnderTest()->isSearchable());
    }

    /**
     * @covers \Ibexa\Core\FieldType\Relation\Type::getRelations
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException
     */
    public function testGetRelations(): void
    {
        $destinationContentId = 7;
        $fieldType = $this->createFieldTypeUnderTest();

        $fieldValue = $fieldType->acceptValue($destinationContentId);
        self::assertInstanceOf(ImageAsset\Value::class, $fieldValue);
        self::assertEquals(
            [
                RelationType::ASSET->value => [$destinationContentId],
            ],
            $fieldType->getRelations($fieldValue)
        );
    }
}
