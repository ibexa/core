<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Core\Repository\Mapper;

use Ibexa\Contracts\Core\Repository\Exceptions\ContentValidationException;
use Ibexa\Contracts\Core\Repository\Values\Content\ContentInfo;
use Ibexa\Contracts\Core\Repository\Values\Content\Field;
use Ibexa\Contracts\Core\Repository\Values\Content\VersionInfo as APIVersionInfo;
use Ibexa\Core\FieldType\FieldTypeRegistry;
use Ibexa\Core\FieldType\TextLine;
use Ibexa\Core\Persistence\Legacy\Content\Language\Handler;
use Ibexa\Core\Repository\Mapper\ContentMapper;
use Ibexa\Core\Repository\Values\Content\Content;
use Ibexa\Core\Repository\Values\Content\VersionInfo;
use Ibexa\Core\Repository\Values\ContentType\ContentType;
use Ibexa\Core\Repository\Values\ContentType\FieldDefinition;
use Ibexa\Core\Repository\Values\ContentType\FieldDefinitionCollection;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class ContentMapperTest extends TestCase
{
    /** @var Handler&MockObject */
    private Handler $contentLanguageHandler;

    /** @var FieldTypeRegistry&MockObject */
    private FieldTypeRegistry $fieldTypeRegistry;

    private ContentMapper $contentMapper;

    protected function setUp(): void
    {
        $this->contentLanguageHandler = $this->createMock(Handler::class);
        $this->fieldTypeRegistry = $this->createMock(FieldTypeRegistry::class);

        $this->contentMapper = new ContentMapper(
            $this->contentLanguageHandler,
            $this->fieldTypeRegistry
        );
    }

    /**
     * @covers \Ibexa\Core\Repository\ContentService::updateContent
     *
     * @throws ContentValidationException
     */
    public function testUpdateContentGetsProperFieldsToUpdate(): void
    {
        $updatedField = new Field(
            [
                'id' => 1234,
                'value' => new TextLine\Value('updated one'),
                'languageCode' => 'fre-FR',
                'fieldDefIdentifier' => 'name',
                'fieldTypeIdentifier' => 'ibexa_string',
            ]
        );
        $updatedField2 = new Field(
            [
                'id' => 1235,
                'value' => new TextLine\Value('two'),
                'languageCode' => 'fre-FR',
                'fieldDefIdentifier' => 'name',
                'fieldTypeIdentifier' => 'ibexa_string',
            ]
        );
        $updatedFields = [$updatedField, $updatedField2];

        $versionInfo = new VersionInfo(
            [
                'contentInfo' => new ContentInfo(['id' => 422, 'mainLanguageCode' => 'eng-GB']),
                'versionNo' => 7,
                'status' => APIVersionInfo::STATUS_DRAFT,
            ]
        );

        $content = new Content(
            [
                'versionInfo' => $versionInfo,
                'internalFields' => [
                    new Field(
                        [
                            'value' => new TextLine\Value('one'),
                            'languageCode' => 'eng-GB',
                            'fieldDefIdentifier' => 'name',
                            'fieldTypeIdentifier' => 'ibexa_string',
                        ]
                    ),
                    $updatedField2,
                ],
                'contentType' => new ContentType([
                    'fieldDefinitions' => new FieldDefinitionCollection([
                        new FieldDefinition([
                            'identifier' => 'name',
                            'fieldTypeIdentifier' => 'ibexa_string',
                        ]),
                    ]),
                ]),
            ]
        );

        $this->fieldTypeRegistry
            ->expects(self::any())
            ->method('getFieldType')
            ->willReturn(new TextLine\Type());

        $fieldForUpdate = $this->contentMapper->getFieldsForUpdate($updatedFields, $content);

        self::assertSame([$updatedField], $fieldForUpdate);
    }
}
