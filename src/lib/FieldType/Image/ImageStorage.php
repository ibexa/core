<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Core\FieldType\Image;

use Ibexa\Contracts\Core\FieldType\GatewayBasedStorage;
use Ibexa\Contracts\Core\FieldType\StorageGatewayInterface;
use Ibexa\Contracts\Core\Persistence\Content\Field;
use Ibexa\Contracts\Core\Persistence\Content\VersionInfo;
use Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException;
use Ibexa\Core\Base\Exceptions\ContentFieldValidationException;
use Ibexa\Core\Base\Exceptions\InvalidArgumentException;
use Ibexa\Core\FieldType\Image\ImageStorage\Gateway;
use Ibexa\Core\FieldType\Validator\FileExtensionBlackListValidator;
use Ibexa\Core\IO\FilePathNormalizerInterface;
use Ibexa\Core\IO\IOServiceInterface;

/**
 * Converter for Image field type external storage.
 */
class ImageStorage extends GatewayBasedStorage
{
    /** @var IOServiceInterface */
    protected $ioService;

    /** @var PathGenerator */
    protected $pathGenerator;

    /** @var AliasCleanerInterface */
    protected $aliasCleaner;

    /** @var Gateway */
    protected StorageGatewayInterface $gateway;

    /** @var FilePathNormalizerInterface */
    protected $filePathNormalizer;

    /** @var FileExtensionBlackListValidator */
    protected $fileExtensionBlackListValidator;

    public function __construct(
        StorageGatewayInterface $gateway,
        IOServiceInterface $ioService,
        PathGenerator $pathGenerator,
        AliasCleanerInterface $aliasCleaner,
        FilePathNormalizerInterface $filePathNormalizer,
        FileExtensionBlackListValidator $fileExtensionBlackListValidator
    ) {
        parent::__construct($gateway);
        $this->ioService = $ioService;
        $this->pathGenerator = $pathGenerator;
        $this->aliasCleaner = $aliasCleaner;
        $this->filePathNormalizer = $filePathNormalizer;
        $this->fileExtensionBlackListValidator = $fileExtensionBlackListValidator;
    }

    /**
     * @throws NotFoundException
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\ContentFieldValidationException
     */
    public function storeFieldData(
        VersionInfo $versionInfo,
        Field $field
    ): bool {
        $contentMetaData = [
            'fieldId' => $field->id,
            'versionNo' => $versionInfo->versionNo,
            'languageCode' => $field->languageCode,
        ];

        // new image
        if (isset($field->value->externalData)) {
            $this->fileExtensionBlackListValidator->validateFileExtension($field->value->externalData['fileName']);
            if (!empty($errors = $this->fileExtensionBlackListValidator->getErrors())) {
                $preparedErrors = [];
                $preparedErrors[$field->fieldDefinitionId][$field->languageCode] = $errors;

                throw ContentFieldValidationException::createNewWithMultiline(
                    $preparedErrors,
                    $versionInfo->contentInfo->name
                );
            }

            $targetPath = sprintf(
                '%s/%s',
                $this->pathGenerator->getStoragePathForField(
                    $field->id,
                    $versionInfo->versionNo,
                    $field->languageCode
                ),
                $field->value->externalData['fileName']
            );
            $targetPath = $this->filePathNormalizer->normalizePath($targetPath);

            if (isset($field->value->externalData['inputUri'])) {
                $localFilePath = $field->value->externalData['inputUri'];
                unset($field->value->externalData['inputUri']);

                $binaryFileCreateStruct = $this->ioService->newBinaryCreateStructFromLocalFile($localFilePath);
                $binaryFileCreateStruct->id = $targetPath;
                $binaryFile = $this->ioService->createBinaryFile($binaryFileCreateStruct);

                $imageSize = getimagesize($localFilePath);
                $field->value->externalData['width'] = $imageSize[0];
                $field->value->externalData['height'] = $imageSize[1];
            } elseif (isset($field->value->externalData['id'])) {
                $binaryFile = $this->ioService->loadBinaryFile($field->value->externalData['id']);
            } elseif ($this->ioService->exists($targetPath)) {
                $binaryFile = $this->ioService->loadBinaryFile($targetPath);
            } else {
                throw new InvalidArgumentException('inputUri', 'No source image could be obtained from the given external data');
            }

            $field->value->externalData['imageId'] = $this->buildImageId($versionInfo, $field);
            $field->value->externalData['uri'] = $binaryFile->uri;
            $field->value->externalData['id'] = $binaryFile->id;
            $field->value->externalData['mime'] = $this->ioService->getMimeType($binaryFile->id);

            $field->value->data = array_merge(
                $field->value->externalData,
                $contentMetaData
            );

            $field->value->externalData = null;

            if (!$this->gateway->hasImageReference($field->value->data['uri'], $field->id)) {
                $this->gateway->storeImageReference($field->value->data['uri'], $field->id);
            }
        } else { // existing image from another version
            if ($field->value->data === null) {
                // Store empty value only with content meta data
                $field->value->data = $contentMetaData;

                return false;
            }

            $this->ioService->loadBinaryFile($field->value->data['id']);

            $field->value->data = array_merge(
                $field->value->data,
                $contentMetaData
            );
            $field->value->externalData = null;
        }

        return true;
    }

    public function getFieldData(
        VersionInfo $versionInfo,
        Field $field
    ) {
        if ($field->value->data !== null) {
            $field->value->data['imageId'] = $this->buildImageId($versionInfo, $field);
            $binaryFile = $this->ioService->loadBinaryFile($field->value->data['id']);
            $field->value->data['id'] = $binaryFile->id;
            $field->value->data['fileSize'] = $binaryFile->size;
            $field->value->data['uri'] = $binaryFile->uri;
        }
    }

    public function deleteFieldData(
        VersionInfo $versionInfo,
        array $fieldIds
    ) {
        $fieldXmls = $this->gateway->getXmlForImages($versionInfo->versionNo, $fieldIds);

        foreach ($fieldXmls as $fieldId => $xml) {
            $storedFiles = $this->gateway->extractFilesFromXml($xml);
            if ($storedFiles === null) {
                continue;
            }

            foreach ($storedFiles as $storedFilePath) {
                $this->gateway->removeImageReferences($storedFilePath, $versionInfo->versionNo, $fieldId);
                if (!$this->gateway->isImageReferenced($storedFilePath)) {
                    $binaryFile = $this->ioService->loadBinaryFileByUri($storedFilePath);
                    // remove aliases (real path is prepended with alias prefixes)
                    $this->aliasCleaner->removeAliases($binaryFile->id);
                    // delete original file
                    $this->ioService->deleteBinaryFile($binaryFile);
                }
            }
        }
    }

    public function hasFieldData(): bool
    {
        return true;
    }

    /**
     * @return string
     */
    private function buildImageId(
        VersionInfo $versionInfo,
        Field $field
    ): string {
        return sprintf(
            '%s-%s-%s',
            $versionInfo->contentInfo->id,
            $field->id,
            $versionInfo->versionNo
        );
    }
}
