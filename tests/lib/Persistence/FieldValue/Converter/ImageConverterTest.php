<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Core\Persistence\FieldValue\Converter;

use Ibexa\Contracts\Core\Persistence\Content\FieldTypeConstraints;
use Ibexa\Contracts\Core\Persistence\Content\Type\FieldDefinition;
use Ibexa\Core\IO\IOServiceInterface;
use Ibexa\Core\IO\UrlRedecoratorInterface;
use Ibexa\Core\Persistence\Legacy\Content\FieldValue\Converter\ImageConverter;
use Ibexa\Core\Persistence\Legacy\Content\StorageFieldDefinition;
use PHPUnit\Framework\TestCase;

final class ImageConverterTest extends TestCase
{
    private const array MIME_TYPES = [
        'image/png',
        'image/jpeg',
    ];

    private const string MIME_TYPES_STORAGE_VALUE = '["image\/png","image\/jpeg"]';

    private ImageConverter $imageConverter;

    protected function setUp(): void
    {
        $ioService = $this->createMock(IOServiceInterface::class);
        $urlRedecorator = $this->createMock(UrlRedecoratorInterface::class);

        $this->imageConverter = new ImageConverter(
            $ioService,
            $urlRedecorator
        );
    }

    /**
     * @dataProvider dataProviderForTestToStorageFieldDefinition
     *
     * @throws \JsonException
     */
    public function testToStorageFieldDefinition(
        FieldDefinition $fieldDefinition,
        StorageFieldDefinition $expectedStorageDef
    ): void {
        $storageFieldDefinition = new StorageFieldDefinition();

        $this->imageConverter->toStorageFieldDefinition($fieldDefinition, $storageFieldDefinition);

        self::assertEquals(
            $expectedStorageDef,
            $storageFieldDefinition
        );
    }

    /**
     * @return iterable<
     *      string,
     *      array{
     *          0: \Ibexa\Contracts\Core\Persistence\Content\Type\FieldDefinition,
     *          1: \Ibexa\Core\Persistence\Legacy\Content\StorageFieldDefinition
     * }>
     */
    public function dataProviderForTestToStorageFieldDefinition(): iterable
    {
        yield 'No validators' => [
            new FieldDefinition([
                'fieldTypeConstraints' => new FieldTypeConstraints([
                    'validators' => [],
                ]),
            ]),
            new StorageFieldDefinition([
                'dataFloat1' => 0.0,
                'dataInt2' => 0,
                'dataText1' => 'MB',
                'dataText5' => '[]',
            ]),
        ];

        yield 'FileSizeValidator' => [
            new FieldDefinition([
                'fieldTypeConstraints' => new FieldTypeConstraints([
                    'validators' => [
                        'FileSizeValidator' => [
                            'maxFileSize' => 1.0,
                        ],
                    ],
                ]),
            ]),
            new StorageFieldDefinition([
                'dataFloat1' => 1.0,
                'dataInt2' => 0,
                'dataText1' => 'MB',
                'dataText5' => '[]',
            ]),
        ];

        yield 'AlternativeTextValidator - required' => [
            new FieldDefinition([
                'fieldTypeConstraints' => new FieldTypeConstraints([
                    'validators' => [
                        'AlternativeTextValidator' => [
                            'required' => true,
                        ],
                    ],
                ]),
            ]),
            new StorageFieldDefinition([
                'dataFloat1' => 0.0,
                'dataInt2' => 1,
                'dataText1' => 'MB',
                'dataText5' => '[]',
            ]),
        ];

        yield 'AlternativeTextValidator - not required' => [
            new FieldDefinition([
                'fieldTypeConstraints' => new FieldTypeConstraints([
                    'validators' => [
                        'AlternativeTextValidator' => [
                            'required' => false,
                        ],
                    ],
                ]),
            ]),
            new StorageFieldDefinition([
                'dataFloat1' => 0.0,
                'dataInt2' => 0,
                'dataText1' => 'MB',
                'dataText5' => '[]',
            ]),
        ];

        yield 'mimeTypes' => [
            new FieldDefinition([
                'fieldTypeConstraints' => new FieldTypeConstraints([
                    'fieldSettings' => [
                        'mimeTypes' => self::MIME_TYPES,
                    ],
                ]),
            ]),
            new StorageFieldDefinition([
                'dataFloat1' => 0.0,
                'dataInt2' => 0,
                'dataText1' => 'MB',
                'dataText5' => self::MIME_TYPES_STORAGE_VALUE,
            ]),
        ];
    }

    /**
     * @dataProvider dataProviderForTestToFieldDefinition
     */
    public function testToFieldDefinition(
        StorageFieldDefinition $storageDef,
        FieldDefinition $expectedFieldDefinition
    ): void {
        $fieldDefinition = new FieldDefinition();

        $this->imageConverter->toFieldDefinition($storageDef, $fieldDefinition);

        self::assertEquals(
            $expectedFieldDefinition,
            $fieldDefinition
        );
    }

    public function dataProviderForTestToFieldDefinition(): iterable
    {
        yield [
            new StorageFieldDefinition([
                'dataFloat1' => 0.0,
                'dataInt2' => 0,
                'dataText5' => [],
            ]),
            new FieldDefinition([
                'fieldTypeConstraints' => new FieldTypeConstraints([
                    'validators' => [
                        'FileSizeValidator' => [
                            'maxFileSize' => null,
                        ],
                        'AlternativeTextValidator' => [
                            'required' => false,
                        ],
                    ],
                    'fieldSettings' => [
                        'mimeTypes' => [],
                    ],
                ]),
            ]),
        ];

        yield [
            new StorageFieldDefinition([
                'dataFloat1' => 1.0,
                'dataInt2' => 1,
                'dataText5' => self::MIME_TYPES_STORAGE_VALUE,
            ]),
            new FieldDefinition([
                'fieldTypeConstraints' => new FieldTypeConstraints([
                    'validators' => [
                        'FileSizeValidator' => [
                            'maxFileSize' => 1.0,
                        ],
                        'AlternativeTextValidator' => [
                            'required' => true,
                        ],
                    ],
                    'fieldSettings' => [
                        'mimeTypes' => self::MIME_TYPES,
                    ],
                ]),
            ]),
        ];
    }
}
