<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Integration\Core\Repository\FieldType;

use Doctrine\DBAL\Exception;
use Doctrine\DBAL\ParameterType;
use DOMDocument;
use DOMElement;
use Ibexa\Contracts\Core\Repository\Exceptions\ForbiddenException;
use Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException;
use Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException;
use Ibexa\Contracts\Core\Repository\Exceptions\UnauthorizedException;
use Ibexa\Contracts\Core\Repository\Values\Content\Content;
use Ibexa\Contracts\Core\Repository\Values\Content\Field;
use Ibexa\Contracts\Core\Test\Repository\SetupFactory\Legacy;
use Ibexa\Core\FieldType\Image\IO\Legacy as LegacyIOService;
use Ibexa\Core\FieldType\Image\Value;
use Ibexa\Core\FieldType\Image\Value as ImageValue;
use Ibexa\Core\IO\IOServiceInterface;
use Ibexa\Core\Persistence\Legacy\Content\Gateway;
use stdClass;

/**
 * Integration test for use field type.
 *
 * @group integration
 * @group field-type
 */
class ImageIntegrationTest extends FileSearchBaseIntegrationTestCase
{
    /**
     * Stores the loaded image path for copy test.
     */
    protected static $loadedImagePath;

    /**
     * IOService storage prefix for the tested Type's files.
     *
     * @var string
     */
    protected static $storagePrefixConfigKey = 'ibexa.io.images.storage.prefix';

    protected function getStoragePrefix()
    {
        return $this->getConfigValue(self::$storagePrefixConfigKey);
    }

    /**
     * Sets up fixture data.
     *
     * @return array
     */
    protected function getFixtureData(): array
    {
        return [
            'create' => [
                'fileName' => 'Icy-Night-Flower.jpg',
                'inputUri' => ($path = __DIR__ . '/_fixtures/image.jpg'),
                'alternativeText' => 'My icy flower at night',
                'fileSize' => filesize($path),
            ],
            'update' => [
                'fileName' => 'Blue-Blue-Blue.png',
                'inputUri' => ($path = __DIR__ . '/_fixtures/image.png'),
                'alternativeText' => 'Such a blue …',
                'fileSize' => filesize($path),
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
        return 'ibexa_image';
    }

    /**
     * @return array{
     *      mimeTypes: array{
     *          type: string,
     *          default: array{},
     *      }
     *  }
     */
    public function getSettingsSchema(): array
    {
        return [
            'mimeTypes' => [
                'type' => 'choice',
                'default' => [],
            ],
        ];
    }

    /**
     * Get a valid $fieldSettings value.
     *
     * @return array{
     *     mimeTypes: array<string>,
     * }
     */
    public function getValidFieldSettings(): array
    {
        return [
            'mimeTypes' => [
                'image/jpeg',
                'image/png',
            ],
        ];
    }

    /**
     * Get $fieldSettings value not accepted by the field type.
     *
     * @return array{
     *       somethingUnknown: int,
     *   }
     */
    public function getInvalidFieldSettings(): array
    {
        return [
            'somethingUnknown' => 0,
        ];
    }

    /**
     * Get expected validator schema.
     *
     * @return array{
     *     FileSizeValidator: array{
     *          maxFileSize: array{
     *              type: string,
     *              default: null,
     *          }
     *     },
     *     AlternativeTextValidator: array{
     *          required: array{
     *              type: string,
     *              default: bool,
     *          }
     *     },
     * }
     */
    public function getValidatorSchema(): array
    {
        return [
            'FileSizeValidator' => [
                'maxFileSize' => [
                    'type' => 'numeric',
                    'default' => null,
                ],
            ],
            'AlternativeTextValidator' => [
                'required' => [
                    'type' => 'bool',
                    'default' => false,
                ],
            ],
        ];
    }

    /**
     * Get a valid $validatorConfiguration.
     *
     * @return array{
     *     FileSizeValidator: array{
     *          maxFileSize: numeric,
     *     },
     *     AlternativeTextValidator: array{
     *          required: bool,
     *     },
     * }
     */
    public function getValidValidatorConfiguration(): array
    {
        return [
            'FileSizeValidator' => [
                'maxFileSize' => 2.0,
            ],
            'AlternativeTextValidator' => [
                'required' => true,
            ],
        ];
    }

    /**
     * Get $validatorConfiguration not accepted by the field type.
     *
     * @return array{
     *     StringLengthValidator: array{
     *          minStringLength: stdClass,
     *     },
     * }
     */
    public function getInvalidValidatorConfiguration(): array
    {
        return [
            'StringLengthValidator' => [
                'minStringLength' => new stdClass(),
            ],
        ];
    }

    /**
     * Get initial field data for valid object creation.
     */
    public function getValidCreationFieldData(): ImageValue
    {
        $fixtureData = $this->getFixtureData();

        return new ImageValue($fixtureData['create']);
    }

    /**
     * Get name generated by the given field type (via fieldType->getName()).
     *
     * @return string
     */
    public function getFieldName(): string
    {
        return 'My icy flower at night';
    }

    /**
     * Asserts that the field data was loaded correctly.
     *
     * Asserts that the data provided by {@link getValidCreationFieldData()}
     * was stored and loaded correctly.
     */
    public function assertFieldDataLoadedCorrect(Field $field): void
    {
        self::assertInstanceOf(
            ImageValue::class,
            $field->value
        );

        $fixtureData = $this->getFixtureData();
        $expectedData = $fixtureData['create'];

        // Will be nullified by external storage
        $expectedData['inputUri'] = null;

        // Will be changed by external storage as fileName will be decorated with a hash
        $expectedData['fileName'] = $field->value->fileName;

        $this->assertPropertiesCorrect(
            $expectedData,
            $field->value
        );

        self::assertTrue(
            $this->uriExistsOnIO($field->value->uri),
            "Asserting that {$field->value->uri} exists."
        );

        self::$loadedImagePath = $field->value->id;
    }

    public function provideInvalidCreationFieldData(): array
    {
        return [
            // will fail because the provided file doesn't exist, and fileSize/fileName won't be set
            [
                new ImageValue(
                    [
                        'inputUri' => __DIR__ . '/_fixtures/nofile.png',
                    ]
                ),
                InvalidArgumentException::class,
            ],
        ];
    }

    /**
     * Get update field externals data.
     */
    public function getValidUpdateFieldData(): ImageValue
    {
        $fixtureData = $this->getFixtureData();

        return new ImageValue($fixtureData['update']);
    }

    public function assertUpdatedFieldDataLoadedCorrect(Field $field): void
    {
        self::assertInstanceOf(
            ImageValue::class,
            $field->value
        );

        $fixtureData = $this->getFixtureData();
        $expectedData = $fixtureData['update'];

        // Will change during storage
        $expectedData['inputUri'] = null;

        // Will change during storage as fileName will be decorated with a hash
        $expectedData['fileName'] = $field->value->fileName;

        $expectedData['uri'] = $field->value->uri;

        $this->assertPropertiesCorrect(
            $expectedData,
            $field->value
        );

        self::assertTrue(
            $this->uriExistsOnIO($field->value->uri),
            "Asserting that file {$field->value->uri} exists"
        );
    }

    public function provideInvalidUpdateFieldData(): array
    {
        return $this->provideInvalidCreationFieldData();
    }

    /**
     * Asserts the the field data was loaded correctly.
     *
     * Asserts that the data provided by {@link getValidCreationFieldData()}
     * was copied and loaded correctly.
     */
    public function assertCopiedFieldDataLoadedCorrectly(Field $field): void
    {
        $this->assertFieldDataLoadedCorrect($field);

        self::assertEquals(
            self::$loadedImagePath,
            $field->value->id
        );
    }

    /**
     * Get data to test to hash method.
     *
     * This is a PHPUnit data provider
     *
     * The returned records must have the the original value assigned to the
     * first index and the expected hash result to the second. For example:
     *
     * <code>
     * array(
     *      array(
     *          new MyValue( true ),
     *          array( 'myValue' => true ),
     *      ),
     *      // ...
     * );
     * </code>
     *
     * @return array
     */
    public function provideToHashData(): array
    {
        return [
            [
                new ImageValue(
                    [
                        'inputUri' => ($path = __DIR__ . '/_fixtures/image.jpg'),
                        'fileName' => 'Icy-Night-Flower.jpg',
                        'alternativeText' => 'My icy flower at night',
                    ]
                ),
                [
                    'inputUri' => $path,
                    'fileName' => 'Icy-Night-Flower.jpg',
                    'alternativeText' => 'My icy flower at night',
                    'fileSize' => null,
                    'id' => null,
                    'imageId' => null,
                    'uri' => null,
                    'width' => null,
                    'height' => null,
                    'additionalData' => [],
                    'mime' => null,
                ],
            ],
            [
                new ImageValue(
                    [
                        'id' => $path = 'var/test/storage/images/file.png',
                        'fileName' => 'Icy-Night-Flower.jpg',
                        'alternativeText' => 'My icy flower at night',
                        'fileSize' => 23,
                        'imageId' => '1-2',
                        'uri' => "/$path",
                        'width' => 123,
                        'height' => 456,
                        'mime' => 'image/png',
                    ]
                ),
                [
                    'id' => $path,
                    'fileName' => 'Icy-Night-Flower.jpg',
                    'alternativeText' => 'My icy flower at night',
                    'fileSize' => 23,
                    'inputUri' => null,
                    'imageId' => '1-2',
                    'uri' => "/$path",
                    'width' => 123,
                    'height' => 456,
                    'additionalData' => [],
                    'mime' => 'image/png',
                ],
            ],
        ];
    }

    /**
     * Get expectations for the fromHash call on our field value.
     *
     * This is a PHPUnit data provider
     *
     * @return array
     */
    public function provideFromHashData(): array
    {
        $fixture = $this->getFixtureData();

        return [
            [
                $fixture['create'],
                $this->getValidCreationFieldData(),
            ],
        ];
    }

    public function testInherentCopyForNewLanguage(): void
    {
        $repository = $this->getRepository();
        $contentService = $repository->getContentService();

        $type = $this->createContentType(
            $this->getValidFieldSettings(),
            $this->getValidValidatorConfiguration(),
            [],
            // Causes a copy of the image value for each language in legacy
            // storage
            ['isTranslatable' => false]
        );

        $draft = $this->createContent($this->getValidCreationFieldData(), $type);

        $updateStruct = $contentService->newContentUpdateStruct();
        $updateStruct->initialLanguageCode = 'ger-DE';
        $updateStruct->setField('name', 'Sindelfingen');

        // Automatically creates a copy of the image field in the back ground
        $updatedDraft = $contentService->updateContent($draft->versionInfo, $updateStruct);

        $paths = [];
        foreach ($updatedDraft->getFields() as $field) {
            if ($field->fieldDefIdentifier === 'data') {
                $paths[$field->languageCode] = $field->value->uri;
            }
        }

        self::assertTrue(
            isset($paths['eng-US']) && isset($paths['ger-DE']),
            'Failed asserting that file path for all languages were found in draft'
        );

        self::assertEquals(
            $paths['eng-US'],
            $paths['ger-DE']
        );

        $contentService->deleteContent($updatedDraft->contentInfo);

        foreach ($paths as $uri) {
            self::assertFalse(
                $this->uriExistsOnIO($uri),
                "$uri has not been removed"
            );
        }
    }

    /**
     * @return array<array{
     *     Value
     * }>
     */
    public function providerForTestIsEmptyValue(): array
    {
        return [
            [new ImageValue()],
        ];
    }

    /**
     * @return array<array{
     *     Value
     * }>
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
     * Covers EZP-23080.
     */
    public function testUpdatingImageMetadataOnlyWorks(): void
    {
        $repository = $this->getRepository();
        $contentService = $repository->getContentService();

        $type = $this->createContentType(
            $this->getValidFieldSettings(),
            $this->getValidValidatorConfiguration(),
            []
        );

        $draft = $this->createContent($this->getValidCreationFieldData(), $type);

        /** @var Value $imageFieldValue */
        $imageFieldValue = $draft->getFieldValue('data');
        $initialValueImageUri = $imageFieldValue->uri;

        // update alternative text
        $imageFieldValue->alternativeText = __METHOD__;
        $updateStruct = $contentService->newContentUpdateStruct();
        $updateStruct->setField('data', $imageFieldValue);
        $updatedDraft = $contentService->updateContent($draft->versionInfo, $updateStruct);

        /** @var Value $updatedImageValue */
        $updatedImageValue = $updatedDraft->getFieldValue('data');

        self::assertEquals($initialValueImageUri, $updatedImageValue->uri);
        self::assertEquals(__METHOD__, $updatedImageValue->alternativeText);
    }

    /**
     * @see https://issues.ibexa.co/browse/EZP-23152
     *
     * @throws NotFoundException
     * @throws ForbiddenException
     * @throws UnauthorizedException
     */
    public function testThatRemovingDraftDoesntRemovePublishedImages(): void
    {
        $repository = $this->getRepository();

        // Load services
        $contentService = $repository->getContentService();

        // create content and publish image
        $content = $this->publishNewImage(
            'EZP23152_1',
            $this->getValidCreationFieldData(),
            [2]
        );
        $originalFileUri = $this->getImageURI($content);

        self::assertTrue(
            $this->uriExistsOnIO($originalFileUri),
            "Asserting image file $originalFileUri exists."
        );

        // Create a new draft and update it
        $updatedDraft = $contentService->createContentDraft($content->contentInfo);
        $contentUpdateStruct = $contentService->newContentUpdateStruct();
        $contentUpdateStruct->initialLanguageCode = 'eng-GB';
        $contentUpdateStruct->setField('name', 'EZP23152_2');
        $updatedDraft = $contentService->updateContent($updatedDraft->versionInfo, $contentUpdateStruct);

        // remove the newly published content version, verify that the original file exists
        $contentService->deleteVersion($updatedDraft->versionInfo);
        self::assertTrue(
            $this->uriExistsOnIO($originalFileUri),
            "Asserting original image file $originalFileUri exists."
        );

        // delete content
        $contentService->deleteContent($content->contentInfo);
        self::assertFalse(
            $this->uriExistsOnIO($originalFileUri),
            "Asserting image file $originalFileUri has been removed."
        );
    }

    /**
     * @throws ForbiddenException
     * @throws NotFoundException
     * @throws UnauthorizedException
     */
    public function testUpdateImageAltTextOnly(): void
    {
        $content = $this->publishNewImage(
            __METHOD__,
            new ImageValue(
                [
                    'inputUri' => __DIR__ . '/_fixtures/image.jpg',
                    'fileName' => 'image.jpg',
                    'fileSize' => filesize(__DIR__ . '/_fixtures/image.jpg'),
                    'alternativeText' => 'Initial alternative text',
                ]
            ),
            [2]
        );

        /** @var Value $imageField */
        $imageField = $content->getFieldValue('image');
        $updatedAlternativeText = 'Updated alternative text';
        $imageField->alternativeText = $updatedAlternativeText;

        $content = $this->updateImage($content, $imageField);

        self::assertSame(
            $updatedAlternativeText,
            $content->getFieldValue('image')->alternativeText
        );
    }

    protected function checkSearchEngineSupport(): void
    {
        if ($this->getSetupFactory() instanceof Legacy) {
            self::markTestSkipped(
                "'ibexa_image' field type is not searchable with Legacy Search Engine"
            );
        }
    }

    protected function getValidSearchValueOne(): ImageValue
    {
        return new ImageValue(
            [
                'fileName' => '1234eeee1234-cafe-terrace-at-night.jpg',
                'inputUri' => ($path = __DIR__ . '/_fixtures/1234eeee1234-image.jpg'),
                'alternativeText' => 'café terrace at night, also known as the cafe terrace on the place du forum',
                'fileSize' => filesize($path),
            ]
        );
    }

    protected function getValidSearchValueTwo(): ImageValue
    {
        return new ImageValue(
            [
                'fileName' => '2222eeee1111-thatched-cottages-at-cordeville.png',
                'inputUri' => ($path = __DIR__ . '/_fixtures/2222eeee1111-image.png'),
                'alternativeText' => 'chaumes de cordeville à auvers-sur-oise',
                'fileSize' => filesize($path),
            ]
        );
    }

    protected function getSearchTargetValueOne(): string
    {
        $value = $this->getValidSearchValueOne();

        /**
         * ensure case-insensitivity.
         *
         * @phpstan-ignore-next-line
         */
        return strtoupper($value->fileName);
    }

    protected function getSearchTargetValueTwo(): string
    {
        $value = $this->getValidSearchValueTwo();

        /**
         * ensure case-insensitivity.
         *
         * @phpstan-ignore-next-line
         */
        return strtoupper($value->fileName);
    }

    protected function getAdditionallyIndexedFieldData(): array
    {
        return [
            [
                'alternative_text',
                $this->getValidSearchValueOne()->alternativeText,
                $this->getValidSearchValueTwo()->alternativeText,
            ],
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
     * @throws ForbiddenException
     * @throws NotFoundException
     * @throws UnauthorizedException
     */
    public function testRemovingContentRemovesImages(): void
    {
        $repository = $this->getRepository();

        // Load services
        $contentService = $repository->getContentService();

        $content = $this->publishNewImage('My Image', $this->getValidCreationFieldData());
        $originalFileUri = $this->getImageURI($content);

        // sanity check
        self::assertTrue(
            $this->uriExistsOnIO($originalFileUri),
            "Asserting image file $originalFileUri exists"
        );

        $content = $this->updateImage($content, $this->getValidUpdateFieldData());
        $updatedFileUri = $this->getImageURI($content);

        // sanity check
        self::assertNotEquals($originalFileUri, $updatedFileUri);

        $contentService->deleteContent($content->contentInfo);

        self::assertFalse(
            $this->uriExistsOnIO($updatedFileUri),
            "Asserting updated image file $updatedFileUri has been removed"
        );

        self::assertFalse(
            $this->uriExistsOnIO($originalFileUri),
            "Asserting original image file $originalFileUri has been removed"
        );
    }

    /**
     * @throws ForbiddenException
     * @throws NotFoundException
     * @throws UnauthorizedException
     */
    public function testRemovingDraftRemovesOldImage(): void
    {
        $repository = $this->getRepository();

        // Load services
        $contentService = $repository->getContentService();

        $contentVersion1 = $this->publishNewImage('My Image', $this->getValidCreationFieldData());
        $originalFileUri = $this->getImageURI($contentVersion1);

        // sanity check
        self::assertTrue(
            $this->uriExistsOnIO($originalFileUri),
            "Asserting image file $originalFileUri exists"
        );

        $contentVersion2 = $this->updateImage($contentVersion1, $this->getValidUpdateFieldData());
        $updatedFileUri = $this->getImageURI($contentVersion2);

        // delete 1st version with original image
        $contentService->deleteVersion(
            // reload 1st version (its state changed) to delete
            $contentService->loadVersionInfo(
                $contentVersion1->contentInfo,
                $contentVersion1->getVersionInfo()->versionNo
            )
        );

        // updated image should be available, but original image should be gone now
        self::assertTrue(
            $this->uriExistsOnIO($updatedFileUri),
            "Asserting image file {$updatedFileUri} exists"
        );

        self::assertFalse(
            $this->uriExistsOnIO($originalFileUri),
            "Asserting image file {$originalFileUri} has been removed"
        );
    }

    /**
     * @throws \Doctrine\DBAL\Driver\Exception
     * @throws Exception
     * @throws \ErrorException
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\Exception
     */
    public function testDeleteImageWithCorruptedName(): void
    {
        $ioService = $this->getSetupFactory()->getServiceContainer()->get(LegacyIOService::class);
        $repository = $this->getRepository();
        $contentService = $repository->getContentService();

        self::assertInstanceOf(IOServiceInterface::class, $ioService);

        $content = $this->publishNewImage(
            __METHOD__,
            new ImageValue(
                [
                    'inputUri' => __DIR__ . '/_fixtures/image.jpg',
                    'fileName' => 'image.jpg',
                    'fileSize' => filesize(__DIR__ . '/_fixtures/image.jpg'),
                    'alternativeText' => 'Alternative',
                ]
            ),
            [2]
        );

        $imageFieldDefinition = $content->getContentType()->getFieldDefinition('image');
        self::assertNotNull($imageFieldDefinition);

        // sanity check
        $this->assertImageExists(true, $ioService, $content);

        $record = $this->fetchXML(
            $content->id,
            $content->getVersionInfo()->versionNo,
            $imageFieldDefinition->id
        );

        $document = $this->corruptImageFieldXML($record);

        $this->updateXML(
            $content->id,
            $content->getVersionInfo()->versionNo,
            $imageFieldDefinition->id,
            $document
        );

        // reload content to get the field value with the corrupted path
        $content = $contentService->loadContent($content->id);
        $this->assertImageExists(false, $ioService, $content);

        $contentService->deleteContent($content->getVersionInfo()->getContentInfo());

        // Expect no League\Flysystem\CorruptedPathDetected thrown
    }

    /**
     * @return array<string,mixed>
     *
     * @throws Exception
     * @throws \ErrorException
     * @throws \Doctrine\DBAL\Driver\Exception
     */
    private function fetchXML(
        int $contentId,
        int $versionNo,
        int $fieldDefinitionId
    ): array {
        $connection = $this->getRawDatabaseConnection();

        $query = $connection->createQueryBuilder();
        $query
            ->select('data_text')
            ->from(Gateway::CONTENT_FIELD_TABLE)
            ->andWhere('content_type_field_definition_id = :content_type_field_definition_id')
            ->andWhere('version = :version')
            ->andWhere('contentobject_id = :contentobject_id')
            ->setParameter('content_type_field_definition_id', $fieldDefinitionId, ParameterType::INTEGER)
            ->setParameter('version', $versionNo, ParameterType::INTEGER)
            ->setParameter('contentobject_id', $contentId, ParameterType::INTEGER);

        $result = $query->executeQuery()->fetchAssociative();
        self::assertNotFalse($result);

        return $result;
    }

    /**
     * @param array<string,mixed> $row
     */
    private function corruptImageFieldXML(array $row): DOMDocument
    {
        $corruptedChar = '­';

        $document = new DOMDocument('1.0', 'utf-8');
        $document->loadXML($row['data_text']);
        $elements = $document->getElementsByTagName('ezimage');
        $element = $elements->item(0);
        self::assertInstanceOf(DOMElement::class, $element);
        $element->setAttribute('filename', $element->getAttribute('filename') . $corruptedChar);
        $element->setAttribute('url', $element->getAttribute('url') . $corruptedChar);

        return $document;
    }

    /**
     * @throws Exception
     * @throws \ErrorException
     */
    private function updateXML(
        int $contentId,
        int $versionNo,
        int $fieldDefinitionId,
        DOMDocument $document
    ): void {
        $connection = $this->getRawDatabaseConnection();

        $query = $connection->createQueryBuilder();
        $query
            ->update(Gateway::CONTENT_FIELD_TABLE)
            ->set('data_text', ':data_text')
            ->setParameter('data_text', $document->saveXML(), ParameterType::STRING)
            ->andWhere('content_type_field_definition_id = :content_type_field_definition_id')
            ->andWhere('version = :version')
            ->andWhere('contentobject_id = :contentobject_id')
            ->setParameter('content_type_field_definition_id', $fieldDefinitionId, ParameterType::INTEGER)
            ->setParameter('version', $versionNo, ParameterType::INTEGER)
            ->setParameter('contentobject_id', $contentId, ParameterType::INTEGER);

        $query->executeQuery();
    }

    /**
     * @throws ForbiddenException
     * @throws NotFoundException
     * @throws UnauthorizedException
     */
    private function publishNewImage(
        string $name,
        ImageValue $imageValue,
        array $parentLocationIDs = []
    ): Content {
        $repository = $this->getRepository(false);
        $contentService = $repository->getContentService();
        $locationService = $repository->getLocationService();
        $contentTypeService = $repository->getContentTypeService();

        $contentCreateStruct = $contentService->newContentCreateStruct(
            $contentTypeService->loadContentTypeByIdentifier('image'),
            'eng-GB'
        );
        $contentCreateStruct->setField('name', $name);
        $contentCreateStruct->setField('image', $imageValue);

        $locationCreateStructList = [];
        foreach ($parentLocationIDs as $parentLocationID) {
            $locationCreateStructList[] = $locationService->newLocationCreateStruct(
                $parentLocationID
            );
        }

        return $contentService->publishVersion(
            $contentService
                ->createContent($contentCreateStruct, $locationCreateStructList)
                ->getVersionInfo()
        );
    }

    /**
     * @throws ForbiddenException
     * @throws UnauthorizedException
     * @throws NotFoundException
     */
    private function updateImage(
        Content $publishedImageContent,
        ImageValue $newImageValue
    ): Content {
        $repository = $this->getRepository(false);
        $contentService = $repository->getContentService();

        $contentDraft = $contentService->createContentDraft($publishedImageContent->contentInfo);
        $contentUpdateStruct = $contentService->newContentUpdateStruct();
        $contentUpdateStruct->setField('image', $newImageValue);
        $contentService->updateContent($contentDraft->getVersionInfo(), $contentUpdateStruct);

        $content = $contentService->publishVersion($contentDraft->getVersionInfo());

        // reload Content to make sure proper data has been persisted
        return $contentService->loadContentByContentInfo($content->contentInfo);
    }

    private function getImageURI(Content $content): string
    {
        return $content->getFieldValue('image')->uri;
    }

    private function assertImageExists(
        bool $expectExists,
        IOServiceInterface $ioService,
        Content $content
    ): void {
        $imageField = $content->getField('image');
        self::assertNotNull($imageField, 'Image field not found');

        /** @var Value $imageFieldValue */
        $imageFieldValue = $imageField->value;
        self::assertSame($expectExists, $ioService->exists($imageFieldValue->id));
    }
}
