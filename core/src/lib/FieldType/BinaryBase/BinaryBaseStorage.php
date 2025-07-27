<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Core\FieldType\BinaryBase;

use Ibexa\Contracts\Core\FieldType\BinaryBase\PathGeneratorInterface;
use Ibexa\Contracts\Core\FieldType\BinaryBase\RouteAwarePathGenerator;
use Ibexa\Contracts\Core\FieldType\GatewayBasedStorage;
use Ibexa\Contracts\Core\FieldType\StorageGatewayInterface;
use Ibexa\Contracts\Core\IO\MimeTypeDetector;
use Ibexa\Contracts\Core\Persistence\Content\Field;
use Ibexa\Contracts\Core\Persistence\Content\VersionInfo;
use Ibexa\Core\Base\Exceptions\ContentFieldValidationException;
use Ibexa\Core\FieldType\Validator\FileExtensionBlackListValidator;
use Ibexa\Core\IO\IOServiceInterface;

/**
 * Storage for binary files.
 */
class BinaryBaseStorage extends GatewayBasedStorage
{
    /**
     * An instance of IOService configured to store to the images folder.
     *
     * @var \Ibexa\Core\IO\IOServiceInterface
     */
    protected $ioService;

    protected PathGeneratorInterface $pathGenerator;

    /** @var \Ibexa\Contracts\Core\IO\MimeTypeDetector */
    protected $mimeTypeDetector;

    protected PathGeneratorInterface $downloadUrlGenerator;

    /** @var \Ibexa\Core\FieldType\BinaryBase\BinaryBaseStorage\Gateway */
    protected StorageGatewayInterface $gateway;

    /** @var \Ibexa\Core\FieldType\Validator\FileExtensionBlackListValidator */
    protected $fileExtensionBlackListValidator;

    public function __construct(
        StorageGatewayInterface $gateway,
        IOServiceInterface $ioService,
        PathGeneratorInterface $pathGenerator,
        MimeTypeDetector $mimeTypeDetector,
        FileExtensionBlackListValidator $fileExtensionBlackListValidator
    ) {
        parent::__construct($gateway);
        $this->ioService = $ioService;
        $this->pathGenerator = $pathGenerator;
        $this->mimeTypeDetector = $mimeTypeDetector;
        $this->fileExtensionBlackListValidator = $fileExtensionBlackListValidator;
    }

    public function setDownloadUrlGenerator(PathGeneratorInterface $downloadUrlGenerator)
    {
        $this->downloadUrlGenerator = $downloadUrlGenerator;
    }

    /**
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\ContentFieldValidationException
     */
    public function storeFieldData(VersionInfo $versionInfo, Field $field)
    {
        if ($field->value->externalData === null) {
            $this->deleteFieldData($versionInfo, [$field->id]);

            return false;
        }

        if (isset($field->value->externalData['inputUri'])) {
            $this->fileExtensionBlackListValidator->validateFileExtension($field->value->externalData['fileName']);
            if (!empty($errors = $this->fileExtensionBlackListValidator->getErrors())) {
                $preparedErrors = [];
                $preparedErrors[$field->fieldDefinitionId][$field->languageCode] = $errors;

                throw ContentFieldValidationException::createNewWithMultiline(
                    $preparedErrors,
                    $versionInfo->contentInfo->name
                );
            }

            $field->value->externalData['mimeType'] = $this->mimeTypeDetector->getFromPath($field->value->externalData['inputUri']);
            $createStruct = $this->ioService->newBinaryCreateStructFromLocalFile($field->value->externalData['inputUri']);
            $createStruct->id = $this->pathGenerator->getStoragePathForField($field, $versionInfo);
            $binaryFile = $this->ioService->createBinaryFile($createStruct);

            $field->value->externalData['id'] = $binaryFile->id;
            $field->value->externalData['mimeType'] = $createStruct->mimeType;
            $field->value->externalData['uri'] = isset($this->downloadUrlGenerator) ?
                $this->downloadUrlGenerator->getStoragePathForField($field, $versionInfo) :
                $binaryFile->uri;
        }

        // copy from another field
        if (!isset($field->value->externalData['mimeType']) && isset($field->value->externalData['id'])) {
            $field->value->externalData['mimeType'] = $this->ioService->getMimeType($field->value->externalData['id']);
        }

        $referenced = $this->gateway->getReferencedFiles([$field->id], $versionInfo->versionNo);
        if ($referenced === null || !in_array($field->value->externalData['id'], $referenced)) {
            $this->removeOldFile($field->id, $versionInfo->versionNo);
        }

        $this->gateway->storeFileReference($versionInfo, $field);
    }

    public function copyLegacyField(VersionInfo $versionInfo, Field $field, Field $originalField)
    {
        if ($originalField->value->externalData === null) {
            return false;
        }

        // field translations have their own file reference, but to the original file
        $field->value->externalData['id'] = $originalField->value->externalData['id'];
        $field->value->externalData['mimeType'] = $originalField->value->externalData['mimeType'];
        $field->value->externalData['uri'] = $originalField->value->externalData['uri'];

        return $this->gateway->storeFileReference($versionInfo, $field);
    }

    /**
     * Removes the old file referenced by $fieldId in $versionNo, if not
     * referenced else where.
     *
     * @param mixed $fieldId
     * @param string $versionNo
     */
    protected function removeOldFile($fieldId, $versionNo)
    {
        $fileReference = $this->gateway->getFileReferenceData($fieldId, $versionNo);
        if ($fileReference === null) {
            // No previous file
            return;
        }

        $this->gateway->removeFileReference($fieldId, $versionNo);

        $fileCounts = $this->gateway->countFileReferences([$fileReference['id']]);

        if ($fileCounts[$fileReference['id']] === 0) {
            $binaryFile = $this->ioService->loadBinaryFile($fileReference['id']);
            $this->ioService->deleteBinaryFile($binaryFile);
        }
    }

    public function getFieldData(VersionInfo $versionInfo, Field $field)
    {
        $field->value->externalData = $this->gateway->getFileReferenceData($field->id, $versionInfo->versionNo);
        if ($field->value->externalData !== null) {
            $binaryFile = $this->ioService->loadBinaryFile($field->value->externalData['id']);
            $field->value->externalData['fileSize'] = $binaryFile->size;

            $uri = $binaryFile->uri;
            if (isset($this->downloadUrlGenerator)) {
                $uri = $this->downloadUrlGenerator->getStoragePathForField($field, $versionInfo);

                if ($this->downloadUrlGenerator instanceof RouteAwarePathGenerator) {
                    $field->value->externalData['route'] = $this->downloadUrlGenerator->getRoute($field, $versionInfo);
                    $field->value->externalData['route_parameters'] = $this->downloadUrlGenerator->getParameters(
                        $field,
                        $versionInfo
                    );
                }
            }

            $field->value->externalData['uri'] = $uri;
        }
    }

    public function deleteFieldData(VersionInfo $versionInfo, array $fieldIds)
    {
        if (empty($fieldIds)) {
            return;
        }

        $referencedFiles = $this->gateway->getReferencedFiles($fieldIds, $versionInfo->versionNo);

        $this->gateway->removeFileReferences($fieldIds, $versionInfo->versionNo);

        $referenceCountMap = $this->gateway->countFileReferences($referencedFiles);

        foreach ($referenceCountMap as $filePath => $count) {
            if ($count === 0) {
                $binaryFile = $this->ioService->loadBinaryFile($filePath);
                $this->ioService->deleteBinaryFile($binaryFile);
            }
        }
    }

    public function hasFieldData()
    {
        return true;
    }
}
