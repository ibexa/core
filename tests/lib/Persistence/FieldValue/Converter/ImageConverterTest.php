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
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class ImageConverterTest extends TestCase
{
    private const MIME_TYPES = [
        'image/png',
        'image/jpeg',
    ];

    private const MIME_TYPES_STORAGE_VALUE = '["image\/png","image\/jpeg"]';

    /** @var ImageConverter */
    private $imageConverter;

    /** @var UrlRedecoratorInterface|MockObject */
    private $urlRedecorator;

    /** @var IOServiceInterface|MockObject */
    private $ioService;

    protected function setUp(): void
    {
        $this->ioService = $this->createMock(IOServiceInterface::class);
        $this->urlRedecorator = $this->createMock(UrlRedecoratorInterface::class);

        $this->imageConverter = new ImageConverter(
            $this->ioService,
            $this->urlRedecorator
        );
    }

    /**
     * @dataProvider dataProviderForTestToStorageFieldDefinition
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
