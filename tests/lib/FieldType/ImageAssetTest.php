<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Core\FieldType;

use Ibexa\Contracts\Core\FieldType\Value as SPIValue;
use Ibexa\Contracts\Core\Persistence\Content\Handler as SPIContentHandler;
use Ibexa\Contracts\Core\Persistence\Content\VersionInfo;
use Ibexa\Contracts\Core\Repository\ContentService;
use Ibexa\Contracts\Core\Repository\Values\Content\Content;
use Ibexa\Contracts\Core\Repository\Values\Content\ContentInfo;
use Ibexa\Contracts\Core\Repository\Values\Content\Relation;
use Ibexa\Contracts\Core\Repository\Values\ContentType\ContentType;
use Ibexa\Contracts\Core\Repository\Values\ContentType\FieldDefinition;
use Ibexa\Core\Base\Exceptions\InvalidArgumentException;
use Ibexa\Core\Base\Exceptions\NotFoundException;
use Ibexa\Core\FieldType\Image\Value;
use Ibexa\Core\FieldType\ImageAsset;
use Ibexa\Core\FieldType\ImageAsset\AssetMapper;
use Ibexa\Core\FieldType\ValidationError;
use Ibexa\Core\Repository\Values\ContentType\FieldDefinition as CoreFieldDefinition;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * @group fieldType
 * @group ezimageasset
 */
class ImageAssetTest extends FieldTypeTest
{
    private const DESTINATION_CONTENT_ID = 14;

    /** @var ContentService|MockObject */
    private $contentServiceMock;

    /** @var AssetMapper|MockObject */
    private $assetMapperMock;

    /** @var SPIContentHandler|MockObject */
    private $contentHandlerMock;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->contentServiceMock = $this->createMock(ContentService::class);
        $this->assetMapperMock = $this->createMock(AssetMapper::class);
        $this->contentHandlerMock = $this->createMock(SPIContentHandler::class);
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

    /**
     * {@inheritdoc}
     */
    protected function provideFieldTypeIdentifier(): string
    {
        return ImageAsset\Type::FIELD_TYPE_IDENTIFIER;
    }

    /**
     * {@inheritdoc}
     */
    protected function createFieldTypeUnderTest()
    {
        return new ImageAsset\Type(
            $this->contentServiceMock,
            $this->assetMapperMock,
            $this->contentHandlerMock
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function getValidatorConfigurationSchemaExpectation(): array
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    protected function getSettingsSchemaExpectation(): array
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    protected function getEmptyValueExpectation()
    {
        return new ImageAsset\Value();
    }

    /**
     * {@inheritdoc}
     */
    public function provideInvalidInputForAcceptValue(): array
    {
        return [
            [
                true,
                InvalidArgumentException::class,
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function provideValidInputForAcceptValue(): array
    {
        $destinationContentId = 7;

        return [
            [
                null,
                $this->getEmptyValueExpectation(),
            ],
            [
                $destinationContentId,
                new ImageAsset\Value($destinationContentId),
            ],
            [
                new ContentInfo([
                    'id' => $destinationContentId,
                ]),
                new ImageAsset\Value($destinationContentId),
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function provideInputForToHash(): array
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

    /**
     * {@inheritdoc}
     */
    public function provideInputForFromHash(): array
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
            'hash without alternativeText' => [
                [
                    'destinationContentId' => $destinationContentId,
                ],
                new ImageAsset\Value($destinationContentId),
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function provideInvalidDataForValidate(): array
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function testValidateNonAsset()
    {
        $destinationContentId = 7;
        $destinationContent = $this->createMock(Content::class);
        $invalidContentTypeIdentifier = 'article';
        $invalidContentType = $this->createMock(ContentType::class);
        $invalidContentType
            ->expects($this->once())
            ->method('__get')
            ->with('identifier')
            ->willReturn($invalidContentTypeIdentifier);

        $destinationContent
            ->method('getContentType')
            ->willReturn($invalidContentType);

        $this->contentServiceMock
            ->expects($this->once())
            ->method('loadContent')
            ->with($destinationContentId)
            ->willReturn($destinationContent);

        $this->assetMapperMock
            ->expects($this->once())
            ->method('isAsset')
            ->with($destinationContent)
            ->willReturn(false);

        $validationErrors = $this->doValidate([], new ImageAsset\Value($destinationContentId));

        $this->assertIsArray($validationErrors);
        $this->assertEquals([
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

    /**
     * @dataProvider provideDataForTestValidateMissingTarget
     *
     * @param array<ValidationError> $expectedValidationErrors
     */
    public function testValidateMissingTarget(
        bool $isRequired,
        array $expectedValidationErrors
    ): void {
        $destinationContentId = 7;

        $this->contentServiceMock
            ->expects($this->once())
            ->method('loadContent')
            ->with($destinationContentId)
            ->willThrowException(new NotFoundException('Content', $destinationContentId));

        $this->assetMapperMock
            ->expects($this->never())
            ->method('isAsset');

        $fieldType = $this->createFieldTypeWithDeletedTarget($destinationContentId);

        $validationErrors = $fieldType->validate(
            new CoreFieldDefinition(['isRequired' => $isRequired]),
            new ImageAsset\Value($destinationContentId)
        );

        $this->assertEquals($expectedValidationErrors, $validationErrors);
    }

    public function testValidateRethrowsWhenTargetExistsWithoutPublishedVersion(): void
    {
        $this->contentServiceMock
            ->expects($this->once())
            ->method('loadContent')
            ->with(self::DESTINATION_CONTENT_ID)
            ->willThrowException(new NotFoundException('Content', self::DESTINATION_CONTENT_ID));

        $this->expectException(NotFoundException::class);

        $this->getFieldTypeUnderTest()->validate(
            new CoreFieldDefinition(['isRequired' => false]),
            new ImageAsset\Value(self::DESTINATION_CONTENT_ID)
        );
    }

    /**
     * @return iterable<string, array{bool, array<ValidationError>}>
     */
    public function provideDataForTestValidateMissingTarget(): iterable
    {
        yield 'optional field' => [false, []];

        yield 'required field' => [
            true,
            [
                new ValidationError(
                    'Content with identifier %contentId% does not exist',
                    null,
                    ['%contentId%' => 7],
                    'destinationContentId'
                ),
            ],
        ];
    }

    public function testToPersistenceValueDropsMissingTarget(): void
    {
        $destinationContentId = 7;
        $fieldType = $this->createFieldTypeWithDeletedTarget($destinationContentId);

        $persistenceValue = $fieldType->toPersistenceValue(
            new ImageAsset\Value($destinationContentId, 'alt text')
        );

        $this->assertSame(
            ['destinationContentId' => null, 'alternativeText' => null],
            $persistenceValue->data
        );
    }

    public function testToPersistenceValueKeepsExistingTarget(): void
    {
        $persistenceValue = $this->getFieldTypeUnderTest()->toPersistenceValue(
            new ImageAsset\Value(self::DESTINATION_CONTENT_ID, 'alt text')
        );

        $this->assertSame(
            ['destinationContentId' => self::DESTINATION_CONTENT_ID, 'alternativeText' => 'alt text'],
            $persistenceValue->data
        );
    }

    /**
     * {@inheritdoc}
     */
    public function provideValidDataForValidate(): array
    {
        return [
            [
                [],
                $this->getEmptyValueExpectation(),
            ],
        ];
    }

    /**
     * @dataProvider provideDataForTestValidateValidNonEmptyAssetValue
     *
     * @param array<ValidationError> $expectedValidationErrors
     */
    public function testValidateValidNonEmptyAssetValue(
        int $fileSize,
        array $expectedValidationErrors
    ): void {
        $destinationContentId = 7;
        $destinationContent = $this->createMock(Content::class);

        $this->contentServiceMock
            ->expects($this->once())
            ->method('loadContent')
            ->with($destinationContentId)
            ->willReturn($destinationContent);

        $this->assetMapperMock
            ->expects($this->once())
            ->method('isAsset')
            ->with($destinationContent)
            ->willReturn(true);

        $assetValueMock = $this->createMock(Value::class);
        $assetValueMock
            ->method('getFileSize')
            ->willReturn($fileSize);

        $this->assetMapperMock
            ->expects($this->once())
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
        $this->assertEquals(
            $expectedValidationErrors,
            $validationErrors
        );
    }

    /**
     * @return iterable<array{
     *     int,
     *     array<ValidationError>,
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

    /**
     * {@inheritdoc}
     */
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
        /** @var FieldDefinition|MockObject $fieldDefinitionMock */
        $fieldDefinitionMock = $this->createMock(FieldDefinition::class);
        $fieldDefinitionMock->method('getFieldSettings')->willReturn($fieldSettings);

        $name = $this->getFieldTypeUnderTest()->getName($value, $fieldDefinitionMock, $languageCode);

        self::assertSame($expected, $name);
    }

    public function testIsSearchable()
    {
        $this->assertTrue($this->getFieldTypeUnderTest()->isSearchable());
    }

    /**
     * @covers \Ibexa\Core\FieldType\Relation\Type::getRelations
     */
    public function testGetRelations()
    {
        $destinationContentId = self::DESTINATION_CONTENT_ID;
        $fieldType = $this->createFieldTypeUnderTest();

        $this->assertEquals(
            [
                Relation::ASSET => [$destinationContentId],
            ],
            $fieldType->getRelations($fieldType->acceptValue($destinationContentId))
        );
    }

    public function testGetRelationsSkipsMissingTarget(): void
    {
        $destinationContentId = 7;
        $fieldType = $this->createFieldTypeWithDeletedTarget($destinationContentId);

        $this->assertSame([], $fieldType->getRelations(new ImageAsset\Value($destinationContentId)));
    }

    private function createFieldTypeWithDeletedTarget(int $destinationContentId): ImageAsset\Type
    {
        $contentHandler = $this->createStub(SPIContentHandler::class);
        $contentHandler
            ->method('loadContentInfo')
            ->willThrowException(new NotFoundException('Content', $destinationContentId));

        return new ImageAsset\Type($this->contentServiceMock, $this->assetMapperMock, $contentHandler);
    }
}

class_alias(ImageAssetTest::class, 'eZ\Publish\Core\FieldType\Tests\ImageAssetTest');
