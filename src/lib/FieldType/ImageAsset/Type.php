<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\FieldType\ImageAsset;

use Ibexa\Contracts\Core\FieldType\Value as SPIValue;
use Ibexa\Contracts\Core\Persistence\Content\Handler as SPIContentHandler;
use Ibexa\Contracts\Core\Repository\ContentService;
use Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException;
use Ibexa\Contracts\Core\Repository\Values\Content\Content;
use Ibexa\Contracts\Core\Repository\Values\Content\ContentInfo;
use Ibexa\Contracts\Core\Repository\Values\Content\RelationType;
use Ibexa\Contracts\Core\Repository\Values\ContentType\FieldDefinition;
use Ibexa\Core\Base\Exceptions\InvalidArgumentType;
use Ibexa\Core\FieldType\FieldType;
use Ibexa\Core\FieldType\ValidationError;
use Ibexa\Core\FieldType\Value as BaseValue;
use JMS\TranslationBundle\Model\Message;
use JMS\TranslationBundle\Translation\TranslationContainerInterface;

class Type extends FieldType implements TranslationContainerInterface
{
    public const FIELD_TYPE_IDENTIFIER = 'ibexa_image_asset';

    /** @var \Ibexa\Contracts\Core\Repository\ContentService */
    private $contentService;

    /** @var \Ibexa\Core\FieldType\ImageAsset\AssetMapper */
    private $assetMapper;

    /** @var \Ibexa\Contracts\Core\Persistence\Content\Handler */
    private $handler;

    public function __construct(
        ContentService $contentService,
        AssetMapper $mapper,
        SPIContentHandler $handler
    ) {
        $this->contentService = $contentService;
        $this->assetMapper = $mapper;
        $this->handler = $handler;
    }

    /**
     * Validates a field based on the validators in the field definition.
     *
     * @param \Ibexa\Contracts\Core\Repository\Values\ContentType\FieldDefinition $fieldDef The field definition of the field
     * @param \Ibexa\Core\FieldType\ImageAsset\Value $value The field value for which an action is performed
     *
     * @return \Ibexa\Contracts\Core\FieldType\ValidationError[]
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\UnauthorizedException
     */
    public function validate(FieldDefinition $fieldDef, SPIValue $value): array
    {
        $errors = [];

        if ($this->isEmptyValue($value)) {
            return $errors;
        }

        $content = $this->contentService->loadContent(
            (int)$value->destinationContentId
        );

        if (!$this->assetMapper->isAsset($content)) {
            return [
                new ValidationError(
                    'Content %type% is not a valid asset target',
                    null,
                    [
                        '%type%' => $content->getContentType()->identifier,
                    ],
                    'destinationContentId'
                ),
            ];
        }

        $validationError = $this->validateMaxFileSize($content);
        if (null !== $validationError) {
            $errors[] = $validationError;
        }

        return $errors;
    }

    /**
     * Returns the field type identifier for this field type.
     *
     * @return string
     */
    public function getFieldTypeIdentifier(): string
    {
        return self::FIELD_TYPE_IDENTIFIER;
    }

    /**
     * @param \Ibexa\Core\FieldType\ImageAsset\Value|\Ibexa\Contracts\Core\FieldType\Value $value
     */
    public function getName(SPIValue $value, FieldDefinition $fieldDefinition, string $languageCode): string
    {
        if (empty($value->destinationContentId)) {
            return '';
        }

        try {
            $contentInfo = $this->handler->loadContentInfo($value->destinationContentId);
            $versionInfo = $this->handler->loadVersionInfo($value->destinationContentId, $contentInfo->currentVersionNo);
        } catch (NotFoundException $e) {
            return '';
        }

        return $versionInfo->names[$languageCode] ?? $versionInfo->names[$contentInfo->mainLanguageCode];
    }

    public function getEmptyValue(): Value
    {
        return new Value();
    }

    public function isEmptyValue(SPIValue $value): bool
    {
        return null === $value->destinationContentId;
    }

    /**
     * Inspects given $inputValue and potentially converts it into a dedicated value object.
     *
     * @param int|string|\Ibexa\Contracts\Core\Repository\Values\Content\ContentInfo|\Ibexa\Core\FieldType\Relation\Value $inputValue
     *
     * @return \Ibexa\Core\FieldType\ImageAsset\Value The potentially converted and structurally plausible value.
     */
    protected function createValueFromInput($inputValue)
    {
        if ($inputValue instanceof ContentInfo) {
            $inputValue = new Value($inputValue->id);
        } elseif (is_int($inputValue) || is_string($inputValue)) {
            $inputValue = new Value($inputValue);
        }

        return $inputValue;
    }

    /**
     * Throws an exception if value structure is not of expected format.
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException If the value does not match the expected structure.
     *
     * @param \Ibexa\Core\FieldType\ImageAsset\Value $value
     */
    protected function checkValueStructure(BaseValue $value): void
    {
        if (!is_int($value->destinationContentId) && !is_string($value->destinationContentId)) {
            throw new InvalidArgumentType(
                '$value->destinationContentId',
                'string|int',
                $value->destinationContentId
            );
        }

        if ($value->alternativeText !== null && !is_string($value->alternativeText)) {
            throw new InvalidArgumentType(
                '$value->alternativeText',
                'string|null',
                $value->alternativeText
            );
        }
    }

    /**
     * @param \Ibexa\Core\FieldType\ImageAsset\Value $value
     */
    protected function getSortInfo(SPIValue $value): false
    {
        return false;
    }

    public function fromHash(mixed $hash): Value
    {
        if (!$hash) {
            return new Value();
        }

        $destinationContentId = $hash['destinationContentId'];
        if ($destinationContentId !== null) {
            $destinationContentId = (int)$destinationContentId;
        }

        return new Value($destinationContentId, $hash['alternativeText']);
    }

    /**
     * @param \Ibexa\Core\FieldType\ImageAsset\Value $value
     *
     * @return array{destinationContentId: int|null, alternativeText: string|null}
     */
    public function toHash(SPIValue $value): array
    {
        $destinationContentId = null;
        if ($value->destinationContentId !== null) {
            $destinationContentId = (int)$value->destinationContentId;
        }

        return [
            'destinationContentId' => $destinationContentId,
            'alternativeText' => $value->alternativeText,
        ];
    }

    /**
     * @param \Ibexa\Core\FieldType\ImageAsset\Value $value
     */
    public function getRelations(SPIValue $value): array
    {
        $relations = [];
        if ($value->destinationContentId !== null) {
            $relations[RelationType::ASSET->value] = [$value->destinationContentId];
        }

        return $relations;
    }

    public function isSearchable(): bool
    {
        return true;
    }

    public static function getTranslationMessages(): array
    {
        return [
            Message::create('ibexa_image_asset.name', 'ibexa_fieldtypes')->setDesc('Image Asset'),
        ];
    }

    /**
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException
     */
    private function validateMaxFileSize(Content $content): ?ValidationError
    {
        $fileSize = $this->assetMapper
            ->getAssetValue($content)
            ->getFileSize();

        $assetValidatorConfiguration = $this->assetMapper
            ->getAssetFieldDefinition()
            ->getValidatorConfiguration();

        $maxFileSizeMB = $assetValidatorConfiguration['FileSizeValidator']['maxFileSize'];
        $maxFileSizeKB = $maxFileSizeMB * 1024 * 1024;

        if (
            $maxFileSizeKB > 0
            && $fileSize > $maxFileSizeKB
        ) {
            return new ValidationError(
                'The file size cannot exceed %size% megabyte.',
                'The file size cannot exceed %size% megabytes.',
                [
                    '%size%' => $maxFileSizeMB,
                ],
                'fileSize'
            );
        }

        return null;
    }
}
