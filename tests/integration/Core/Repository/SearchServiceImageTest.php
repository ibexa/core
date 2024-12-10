<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Integration\Core\Repository;

use Ibexa\Contracts\Core\Repository\Values\Content\Query;
use Ibexa\Contracts\Core\Repository\Values\ContentType\ContentType;
use Ibexa\Contracts\Core\Repository\Values\ContentType\ContentTypeDraft;
use Ibexa\Contracts\Core\Repository\Values\ContentType\FieldDefinition;
use Ibexa\Core\FieldType\Image\Orientation;
use Ibexa\Core\FieldType\Image\Value as ImageValue;
use Ibexa\Core\FieldType\TextLine\Value as TextValue;
use Ibexa\Tests\Integration\Core\RepositorySearchTestCase;

final class SearchServiceImageTest extends RepositorySearchTestCase
{
    private const IMAGE_CONTENT_TYPE = 'image';
    private const IMAGE_FIELD_DEF_IDENTIFIER = 'image';
    private const IMAGE_FILES = [
        'landscape.jpg',
        'portrait.jpg',
        'square.png',
    ];

    private const IMAGE_FIXTURES_DIR_PATH = __DIR__ . '/_fixtures/image/';

    protected function setUp(): void
    {
        parent::setUp();

        $this->createImages();

        $this->refreshSearch();
    }

    /**
     * @dataProvider provideDataForTestCriterion
     * @dataProvider provideInvalidDataForTestCriterion
     */
    public function testCriterion(
        int $expectedCount,
        Query\Criterion $imageCriterion
    ): void {
        if (getenv('SEARCH_ENGINE') === 'legacy') {
            self::markTestSkipped('Image criteria are not supported in Legacy Search Engine');
        }

        $query = new Query();
        $query->filter = new Query\Criterion\LogicalAnd(
            [
                new Query\Criterion\ContentTypeIdentifier(self::IMAGE_CONTENT_TYPE),
                $imageCriterion,
            ]
        );

        $searchHits = self::getSearchService()->findContent($query);

        self::assertSame(
            $expectedCount,
            $searchHits->totalCount
        );
    }

    /**
     * @return iterable<array{
     *     int,
     *     \Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion
     * }>
     */
    public function provideDataForTestCriterion(): iterable
    {
        yield 'Dimensions' => [
            3,
            $this->createDimensionsCriterion(
                0,
                100,
                0,
                100
            ),
        ];

        yield 'FileSize - with numeric values from 0 to 1' => [
            3,
            $this->createFileSizeCriterion(0, 1),
        ];

        yield 'FileSize - with numeric string values from 0.0 to 2.5' => [
            3,
            $this->createFileSizeCriterion('0.0', '2.5'),
        ];

        yield 'FileSize - with numeric values from 0.0 to 2.5' => [
            3,
            $this->createFileSizeCriterion(0.0, 2.5),
        ];

        yield 'FileSize - with numeric values 0.0001 to 0.004' => [
            2,
            $this->createFileSizeCriterion(0.001, 0.004),
        ];

        yield 'FileSize - with values numeric string 0.0003 and numeric 0.3' => [
            1,
            $this->createFileSizeCriterion('0.003', 0.3),
        ];

        yield 'FileSize - min value' => [
            2,
            $this->createFileSizeCriterion('0.0002'),
        ];

        yield 'FileSize - max value' => [
            1,
            $this->createFileSizeCriterion(null, '0.0003'),
        ];

        yield 'Width' => [
            3,
            $this->createWidthCriterion(0, 100),
        ];

        yield 'Height' => [
            3,
            $this->createHeightCriterion(0, 100),
        ];

        yield 'MimeType - single' => [
            2,
            $this->createMimeTypeCriterion('image/jpeg'),
        ];

        yield 'MimeType - multiple' => [
            3,
            $this->createMimeTypeCriterion(
                [
                    'image/jpeg',
                    'image/png',
                ],
            ),
        ];

        yield 'Orientation - landscape' => [
            1,
            $this->createOrientationCriterion(Orientation::LANDSCAPE),
        ];

        yield 'Orientation - portrait' => [
            1,
            $this->createOrientationCriterion(Orientation::PORTRAIT),
        ];

        yield 'Orientation - square' => [
            1,
            $this->createOrientationCriterion(Orientation::SQUARE),
        ];

        yield 'Orientation - multiple' => [
            3,
            $this->createOrientationCriterion(
                [
                    Orientation::LANDSCAPE,
                    Orientation::PORTRAIT,
                    Orientation::SQUARE,
                ]
            ),
        ];

        yield 'Image' => [
            2,
            new Query\Criterion\Image(
                self::IMAGE_FIELD_DEF_IDENTIFIER,
                [
                    'mimeTypes' => [
                        'image/jpeg',
                        'image/png',
                    ],
                    'size' => [
                        'min' => 0,
                        'max' => 1,
                    ],
                    'width' => [
                        'min' => 0,
                        'max' => 100,
                    ],
                    'height' => [
                        'min' => 0,
                        'max' => 100,
                    ],
                    'orientation' => [
                        Orientation::LANDSCAPE,
                        Orientation::PORTRAIT,
                    ],
                ]
            ),
        ];
    }

    /**
     * @return iterable<array{
     *     int,
     *     \Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion
     * }>
     */
    public function provideInvalidDataForTestCriterion(): iterable
    {
        yield 'Dimensions - width and height values too large' => [
            0,
            $this->createDimensionsCriterion(
                101,
                200,
                101,
                300
            ),
        ];

        yield 'FileSize - size value too large' => [
            0,
            $this->createFileSizeCriterion(
                1,
                2
            ),
        ];

        yield 'Width - width value to large' => [
            0,
            $this->createWidthCriterion(101, 200),
        ];

        yield 'Height - height value to large' => [
            0,
            $this->createHeightCriterion(101, 300),
        ];

        yield 'MimeType - invalid single mime type' => [
            0,
            $this->createMimeTypeCriterion('image/invalid'),
        ];

        yield 'MimeType - invalid multiple mime types' => [
            0,
            $this->createMimeTypeCriterion(
                [
                    'image/invalid',
                    'image/gif',
                ]
            ),
        ];
    }

    /**
     * @param string|array<string> $value
     */
    private function createMimeTypeCriterion($value): Query\Criterion\Image\MimeType
    {
        return new Query\Criterion\Image\MimeType(
            self::IMAGE_FIELD_DEF_IDENTIFIER,
            $value
        );
    }

    /**
     * @param numeric|null $min
     * @param numeric|null $max
     */
    private function createFileSizeCriterion(
        $min = 0,
        $max = null
    ): Query\Criterion\Image\FileSize {
        return new Query\Criterion\Image\FileSize(
            self::IMAGE_FIELD_DEF_IDENTIFIER,
            $min,
            $max
        );
    }

    private function createWidthCriterion(
        int $min = 0,
        ?int $max = null
    ): Query\Criterion\Image\Width {
        return new Query\Criterion\Image\Width(
            self::IMAGE_FIELD_DEF_IDENTIFIER,
            $min,
            $max
        );
    }

    private function createHeightCriterion(
        int $min = 0,
        ?int $max = null
    ): Query\Criterion\Image\Height {
        return new Query\Criterion\Image\Height(
            self::IMAGE_FIELD_DEF_IDENTIFIER,
            $min,
            $max
        );
    }

    private function createDimensionsCriterion(
        int $minWidth,
        int $maxWidth,
        int $minHeight,
        int $maxHeight
    ): Query\Criterion\Image\Dimensions {
        return new Query\Criterion\Image\Dimensions(
            self::IMAGE_FIELD_DEF_IDENTIFIER,
            [
                'width' => [
                    'min' => $minWidth,
                    'max' => $maxWidth,
                ],
                'height' => [
                    'min' => $minHeight,
                    'max' => $maxHeight,
                ],
            ]
        );
    }

    /**
     * @param string|array<string> $value
     */
    private function createOrientationCriterion($value): Query\Criterion\Image\Orientation
    {
        return new Query\Criterion\Image\Orientation(
            self::IMAGE_FIELD_DEF_IDENTIFIER,
            $value
        );
    }

    private function createImages(): void
    {
        $contentType = $this->loadContentTypeImage();
        foreach (self::IMAGE_FILES as $image) {
            $this->createContentImage(
                $contentType,
                self::IMAGE_FIXTURES_DIR_PATH . $image,
                $image
            );
        }
    }

    private function createContentImage(
        ContentType $contentType,
        string $path,
        string $fileName
    ): void {
        $contentCreateStruct = self::getContentService()->newContentCreateStruct(
            $contentType,
            'eng-GB'
        );

        $imageValue = new ImageValue();
        $imageValue->fileName = $fileName;
        $imageValue->inputUri = $path;

        $contentCreateStruct->setField('name', new TextValue('Image'), 'eng-GB');
        $contentCreateStruct->setField('image', $imageValue, 'eng-GB');

        $contentService = self::getContentService();
        $contentService->publishVersion(
            $contentService
                ->createContent($contentCreateStruct)
                ->getVersionInfo()
        );
    }

    private function loadContentTypeImage(): ContentType
    {
        $imageContentType = self::getContentTypeService()->loadContentTypeByIdentifier(self::IMAGE_CONTENT_TYPE);

        $this->ensureImageFieldTypeIsSearchable($imageContentType);

        return $imageContentType;
    }

    private function ensureImageFieldTypeIsSearchable(ContentType $contentType): void
    {
        $fieldDefinition = $contentType->getFieldDefinition(self::IMAGE_FIELD_DEF_IDENTIFIER);
        if (
            null === $fieldDefinition
            || $fieldDefinition->isSearchable
        ) {
            return;
        }

        $this->setFieldTypeAsSearchable(
            self::getContentTypeService()->createContentTypeDraft($contentType),
            $fieldDefinition
        );
    }

    /**
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\BadStateException
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\UnauthorizedException
     */
    private function setFieldTypeAsSearchable(
        ContentTypeDraft $contentTypeDraft,
        FieldDefinition $fieldDefinition
    ): void {
        $contentTypeService = self::getContentTypeService();
        $fieldDefinitionUpdateStruct = $contentTypeService->newFieldDefinitionUpdateStruct();
        $fieldDefinitionUpdateStruct->isSearchable = true;

        $contentTypeService = self::getContentTypeService();
        $contentTypeService->updateFieldDefinition(
            $contentTypeDraft,
            $fieldDefinition,
            $fieldDefinitionUpdateStruct
        );
        $contentTypeService->publishContentTypeDraft($contentTypeDraft);
    }
}
