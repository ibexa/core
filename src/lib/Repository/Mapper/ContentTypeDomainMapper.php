<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\Repository\Mapper;

use DateTime;
use Ibexa\Contracts\Core\FieldType\FieldType as SPIFieldType;
use Ibexa\Contracts\Core\Persistence\Content\Language\Handler as SPILanguageHandler;
use Ibexa\Contracts\Core\Persistence\Content\Type as SPIContentType;
use Ibexa\Contracts\Core\Persistence\Content\Type\FieldDefinition as SPIFieldDefinition;
use Ibexa\Contracts\Core\Persistence\Content\Type\Group as SPIContentTypeGroup;
use Ibexa\Contracts\Core\Persistence\Content\Type\Handler as SPITypeHandler;
use Ibexa\Contracts\Core\Persistence\Content\Type\UpdateStruct as SPIContentTypeUpdateStruct;
use Ibexa\Contracts\Core\Repository\Values\ContentType\ContentType as APIContentType;
use Ibexa\Contracts\Core\Repository\Values\ContentType\ContentTypeDraft as APIContentTypeDraft;
use Ibexa\Contracts\Core\Repository\Values\ContentType\ContentTypeGroup as APIContentTypeGroup;
use Ibexa\Contracts\Core\Repository\Values\ContentType\ContentTypeUpdateStruct as APIContentTypeUpdateStruct;
use Ibexa\Contracts\Core\Repository\Values\ContentType\FieldDefinition as APIFieldDefinition;
use Ibexa\Contracts\Core\Repository\Values\ContentType\FieldDefinitionCreateStruct as APIFieldDefinitionCreateStruct;
use Ibexa\Contracts\Core\Repository\Values\ContentType\FieldDefinitionUpdateStruct as APIFieldDefinitionUpdateStruct;
use Ibexa\Contracts\Core\Repository\Values\User\UserReference as APIUserReference;
use Ibexa\Core\Base\Exceptions\ContentTypeFieldDefinitionValidationException;
use Ibexa\Core\FieldType\FieldTypeRegistry;
use Ibexa\Core\FieldType\ValidationError;
use Ibexa\Core\Repository\ProxyFactory\ProxyDomainMapperInterface;
use Ibexa\Core\Repository\Values\ContentType\ContentType;
use Ibexa\Core\Repository\Values\ContentType\ContentTypeDraft;
use Ibexa\Core\Repository\Values\ContentType\ContentTypeGroup;
use Ibexa\Core\Repository\Values\ContentType\FieldDefinition;
use Ibexa\Core\Repository\Values\ContentType\FieldDefinitionCollection;

/**
 * ContentTypeDomainMapper is an internal service.
 *
 * @internal Meant for internal use by Repository.
 */
class ContentTypeDomainMapper extends ProxyAwareDomainMapper
{
    /** @var \Ibexa\Contracts\Core\Persistence\Content\Type\Handler */
    protected $contentTypeHandler;

    /** @var \Ibexa\Contracts\Core\Persistence\Content\Language\Handler */
    protected $contentLanguageHandler;

    /** @var \Ibexa\Core\FieldType\FieldTypeRegistry */
    protected $fieldTypeRegistry;

    public function __construct(
        SPITypeHandler $contentTypeHandler,
        SPILanguageHandler $contentLanguageHandler,
        FieldTypeRegistry $fieldTypeRegistry,
        ?ProxyDomainMapperInterface $proxyFactory = null
    ) {
        $this->contentTypeHandler = $contentTypeHandler;
        $this->contentLanguageHandler = $contentLanguageHandler;
        $this->fieldTypeRegistry = $fieldTypeRegistry;
        parent::__construct($proxyFactory);
    }

    /**
     * Builds a ContentType domain object from value object returned by persistence.
     */
    public function buildContentTypeDomainObject(
        SPIContentType $spiContentType,
        array $prioritizedLanguages = []
    ): APIContentType {
        $mainLanguageCode = $this->contentLanguageHandler->load(
            $spiContentType->initialLanguageId
        )->languageCode;

        $fieldDefinitions = [];
        foreach ($spiContentType->fieldDefinitions as $spiFieldDefinition) {
            $fieldDefinitions[] = $this->buildFieldDefinitionDomainObject(
                $spiFieldDefinition,
                $mainLanguageCode,
                $prioritizedLanguages
            );
        }

        return new ContentType(
            [
                'names' => $spiContentType->name,
                'descriptions' => $spiContentType->description,
                'contentTypeGroups' => $this->proxyFactory->createContentTypeGroupProxyList(
                    $spiContentType->groupIds,
                    $prioritizedLanguages
                ),
                'fieldDefinitions' => new FieldDefinitionCollection($fieldDefinitions),
                'id' => $spiContentType->id,
                'status' => $spiContentType->status,
                'identifier' => $spiContentType->identifier,
                'creationDate' => $this->getDateTime($spiContentType->created),
                'modificationDate' => $this->getDateTime($spiContentType->modified),
                'creatorId' => $spiContentType->creatorId,
                'modifierId' => $spiContentType->modifierId,
                'remoteId' => $spiContentType->remoteId,
                'urlAliasSchema' => $spiContentType->urlAliasSchema ?? '',
                'nameSchema' => $spiContentType->nameSchema,
                'isContainer' => $spiContentType->isContainer,
                'mainLanguageCode' => $mainLanguageCode,
                'defaultAlwaysAvailable' => $spiContentType->defaultAlwaysAvailable,
                'defaultSortField' => $spiContentType->sortField,
                'defaultSortOrder' => $spiContentType->sortOrder,
                'prioritizedLanguages' => $prioritizedLanguages,
                'languageCodes' => $spiContentType->languageCodes,
            ]
        );
    }

    /**
     * Builds ContentType update struct for storage layer.
     *
     * @param \Ibexa\Contracts\Core\Repository\Values\ContentType\ContentTypeDraft $contentTypeDraft
     * @param \Ibexa\Contracts\Core\Repository\Values\ContentType\ContentTypeUpdateStruct $contentTypeUpdateStruct
     * @param \Ibexa\Contracts\Core\Repository\Values\User\UserReference $user
     *
     * @return \Ibexa\Contracts\Core\Persistence\Content\Type\UpdateStruct
     */
    public function buildSPIContentTypeUpdateStruct(APIContentTypeDraft $contentTypeDraft, APIContentTypeUpdateStruct $contentTypeUpdateStruct, APIUserReference $user)
    {
        $updateStruct = new SPIContentTypeUpdateStruct();

        $updateStruct->identifier = $contentTypeUpdateStruct->identifier !== null ?
            $contentTypeUpdateStruct->identifier :
            $contentTypeDraft->identifier;
        $updateStruct->remoteId = $contentTypeUpdateStruct->remoteId !== null ?
            $contentTypeUpdateStruct->remoteId :
            $contentTypeDraft->remoteId;

        $updateStruct->name = $contentTypeUpdateStruct->names !== null ?
            $contentTypeUpdateStruct->names :
            $contentTypeDraft->names;
        $updateStruct->description = $contentTypeUpdateStruct->descriptions !== null ?
            $contentTypeUpdateStruct->descriptions :
            $contentTypeDraft->descriptions;

        $updateStruct->modified = $contentTypeUpdateStruct->modificationDate !== null ?
            $contentTypeUpdateStruct->modificationDate->getTimestamp() :
            time();
        $updateStruct->modifierId = $contentTypeUpdateStruct->modifierId !== null ?
            $contentTypeUpdateStruct->modifierId :
            $user->getUserId();

        $updateStruct->urlAliasSchema = $contentTypeUpdateStruct->urlAliasSchema !== null ?
            $contentTypeUpdateStruct->urlAliasSchema :
            $contentTypeDraft->urlAliasSchema;
        $updateStruct->nameSchema = $contentTypeUpdateStruct->nameSchema !== null ?
            $contentTypeUpdateStruct->nameSchema :
            $contentTypeDraft->nameSchema;

        $updateStruct->isContainer = $contentTypeUpdateStruct->isContainer !== null ?
            $contentTypeUpdateStruct->isContainer :
            $contentTypeDraft->isContainer();
        $updateStruct->sortField = $contentTypeUpdateStruct->defaultSortField !== null ?
            $contentTypeUpdateStruct->defaultSortField :
            $contentTypeDraft->defaultSortField;
        $updateStruct->sortOrder = $contentTypeUpdateStruct->defaultSortOrder !== null ?
            (int)$contentTypeUpdateStruct->defaultSortOrder :
            $contentTypeDraft->defaultSortOrder;

        $updateStruct->defaultAlwaysAvailable = $contentTypeUpdateStruct->defaultAlwaysAvailable !== null ?
            $contentTypeUpdateStruct->defaultAlwaysAvailable :
            $contentTypeDraft->defaultAlwaysAvailable;
        $updateStruct->initialLanguageId = $this->contentLanguageHandler->loadByLanguageCode(
            $contentTypeUpdateStruct->mainLanguageCode !== null ? $contentTypeUpdateStruct->mainLanguageCode : $contentTypeDraft->mainLanguageCode
        )->id;

        return $updateStruct;
    }

    /**
     * Builds a ContentTypeDraft domain object from value object returned by persistence.
     *
     * Decorates ContentType.
     */
    public function buildContentTypeDraftDomainObject(SPIContentType $spiContentType): APIContentTypeDraft
    {
        return new ContentTypeDraft(
            [
                'innerContentType' => $this->buildContentTypeDomainObject($spiContentType),
                'isContainer' => false,
            ]
        );
    }

    /**
     * Builds a ContentTypeGroup domain object from value object returned by persistence.
     */
    public function buildContentTypeGroupDomainObject(SPIContentTypeGroup $spiGroup, array $prioritizedLanguages = []): APIContentTypeGroup
    {
        return new ContentTypeGroup(
            [
                'id' => $spiGroup->id,
                'identifier' => $spiGroup->identifier,
                'creationDate' => $this->getDateTime($spiGroup->created),
                'modificationDate' => $this->getDateTime($spiGroup->modified),
                'creatorId' => $spiGroup->creatorId,
                'modifierId' => $spiGroup->modifierId,
                'names' => $spiGroup->name,
                'descriptions' => $spiGroup->description,
                'prioritizedLanguages' => $prioritizedLanguages,
                'isSystem' => $spiGroup->isSystem,
            ]
        );
    }

    /**
     * Builds a FieldDefinition domain object from value object returned by persistence.
     *
     * @param \Ibexa\Contracts\Core\Persistence\Content\Type\FieldDefinition $spiFieldDefinition
     * @param string $mainLanguageCode
     * @param string[] $prioritizedLanguages
     *
     * @return \Ibexa\Contracts\Core\Repository\Values\ContentType\FieldDefinition
     */
    public function buildFieldDefinitionDomainObject(SPIFieldDefinition $spiFieldDefinition, $mainLanguageCode, array $prioritizedLanguages = [])
    {
        /** @var $fieldType \Ibexa\Contracts\Core\FieldType\FieldType */
        $fieldType = $this->fieldTypeRegistry->getFieldType($spiFieldDefinition->fieldType);
        $fieldDefinition = new FieldDefinition(
            [
                'names' => $spiFieldDefinition->name,
                'descriptions' => $spiFieldDefinition->description,
                'id' => $spiFieldDefinition->id,
                'identifier' => $spiFieldDefinition->identifier,
                'fieldGroup' => $spiFieldDefinition->fieldGroup,
                'position' => $spiFieldDefinition->position,
                'fieldTypeIdentifier' => $spiFieldDefinition->fieldType,
                'isTranslatable' => $spiFieldDefinition->isTranslatable,
                'isThumbnail' => $spiFieldDefinition->isThumbnail,
                'isRequired' => $spiFieldDefinition->isRequired,
                'isInfoCollector' => $spiFieldDefinition->isInfoCollector,
                'defaultValue' => $fieldType->fromPersistenceValue($spiFieldDefinition->defaultValue),
                'isSearchable' => !$fieldType->isSearchable() ? false : $spiFieldDefinition->isSearchable,
                'fieldSettings' => (array)$spiFieldDefinition->fieldTypeConstraints->fieldSettings,
                'validatorConfiguration' => (array)$spiFieldDefinition->fieldTypeConstraints->validators,
                'prioritizedLanguages' => $prioritizedLanguages,
                'mainLanguageCode' => $mainLanguageCode,
            ]
        );

        return $fieldDefinition;
    }

    /**
     * @param \Ibexa\Contracts\Core\Repository\Values\ContentType\FieldDefinitionUpdateStruct $fieldDefinitionUpdateStruct
     * @param \Ibexa\Contracts\Core\Repository\Values\ContentType\FieldDefinition $fieldDefinition
     * @param string $mainLanguageCode
     *
     * @return \Ibexa\Contracts\Core\Persistence\Content\Type\FieldDefinition
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException
     * @throws \Ibexa\Core\Base\Exceptions\ContentTypeFieldDefinitionValidationException
     */
    public function buildSPIFieldDefinitionFromUpdateStruct(
        APIFieldDefinitionUpdateStruct $fieldDefinitionUpdateStruct,
        APIFieldDefinition $fieldDefinition,
        string $mainLanguageCode
    ): SPIFieldDefinition {
        /** @var $fieldType \Ibexa\Contracts\Core\FieldType\FieldType */
        $fieldType = $this->fieldTypeRegistry->getFieldType(
            $fieldDefinition->fieldTypeIdentifier
        );

        $validatorConfiguration = $fieldDefinitionUpdateStruct->validatorConfiguration === null
            ? $fieldDefinition->validatorConfiguration
            : $fieldDefinitionUpdateStruct->validatorConfiguration;
        $fieldSettings = $fieldDefinitionUpdateStruct->fieldSettings === null
            ? $fieldDefinition->fieldSettings
            : $fieldDefinitionUpdateStruct->fieldSettings;

        $validationErrors = [];
        if ($fieldDefinitionUpdateStruct->isSearchable && !$fieldType->isSearchable()) {
            $validationErrors[] = new ValidationError(
                "FieldType '{$fieldDefinition->fieldTypeIdentifier}' is not searchable"
            );
        }
        $validationErrors = array_merge(
            $validationErrors,
            $fieldType->validateValidatorConfiguration($validatorConfiguration),
            $fieldType->validateFieldSettings($fieldSettings)
        );

        if (!empty($validationErrors)) {
            throw new ContentTypeFieldDefinitionValidationException([$fieldDefinition->identifier => $validationErrors]);
        }

        $spiFieldDefinition = new SPIFieldDefinition(
            [
                'id' => $fieldDefinition->id,
                'fieldType' => $fieldDefinition->fieldTypeIdentifier,
                'name' => $fieldDefinitionUpdateStruct->names === null ?
                    $fieldDefinition->getNames() :
                    array_merge($fieldDefinition->getNames(), $fieldDefinitionUpdateStruct->names),
                'description' => $fieldDefinitionUpdateStruct->descriptions === null ?
                    $fieldDefinition->getDescriptions() :
                    array_merge($fieldDefinition->getDescriptions(), $fieldDefinitionUpdateStruct->descriptions),
                'identifier' => $fieldDefinitionUpdateStruct->identifier === null ?
                    $fieldDefinition->identifier :
                    $fieldDefinitionUpdateStruct->identifier,
                'fieldGroup' => $fieldDefinitionUpdateStruct->fieldGroup === null ?
                    $fieldDefinition->fieldGroup :
                    $fieldDefinitionUpdateStruct->fieldGroup,
                'position' => $fieldDefinitionUpdateStruct->position === null ?
                    $fieldDefinition->position :
                    $fieldDefinitionUpdateStruct->position,
                'isTranslatable' => $fieldDefinitionUpdateStruct->isTranslatable === null ?
                    $fieldDefinition->isTranslatable :
                    $fieldDefinitionUpdateStruct->isTranslatable,
                'isThumbnail' => $fieldDefinitionUpdateStruct->isThumbnail === null ?
                    $fieldDefinition->isThumbnail :
                    $fieldDefinitionUpdateStruct->isThumbnail,
                'isRequired' => $fieldDefinitionUpdateStruct->isRequired === null ?
                    $fieldDefinition->isRequired :
                    $fieldDefinitionUpdateStruct->isRequired,
                'isInfoCollector' => $fieldDefinitionUpdateStruct->isInfoCollector === null ?
                    $fieldDefinition->isInfoCollector :
                    $fieldDefinitionUpdateStruct->isInfoCollector,
                'isSearchable' => $fieldDefinitionUpdateStruct->isSearchable === null ?
                    $fieldDefinition->isSearchable :
                    $fieldDefinitionUpdateStruct->isSearchable,
                'mainLanguageCode' => $mainLanguageCode,
                // These properties are precreated in constructor
                //"fieldTypeConstraints"
                //"defaultValue"
            ]
        );

        $spiFieldDefinition->fieldTypeConstraints->validators = $validatorConfiguration;
        $spiFieldDefinition->fieldTypeConstraints->fieldSettings = $fieldSettings;
        $spiFieldDefinition->defaultValue = $fieldType->toPersistenceValue(
            $fieldType->acceptValue($fieldDefinitionUpdateStruct->defaultValue)
        );

        return $spiFieldDefinition;
    }

    /**
     * @param \Ibexa\Contracts\Core\Repository\Values\ContentType\FieldDefinitionCreateStruct $fieldDefinitionCreateStruct
     * @param \Ibexa\Contracts\Core\FieldType\FieldType $fieldType
     * @param string $mainLanguageCode
     *
     * @return \Ibexa\Contracts\Core\Persistence\Content\Type\FieldDefinition
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException
     */
    public function buildSPIFieldDefinitionFromCreateStruct(
        APIFieldDefinitionCreateStruct $fieldDefinitionCreateStruct,
        SPIFieldType $fieldType,
        string $mainLanguageCode
    ): SPIFieldDefinition {
        $spiFieldDefinition = new SPIFieldDefinition(
            [
                'id' => null,
                'identifier' => $fieldDefinitionCreateStruct->identifier,
                'fieldType' => $fieldDefinitionCreateStruct->fieldTypeIdentifier,
                'name' => $fieldDefinitionCreateStruct->names,
                'description' => $fieldDefinitionCreateStruct->descriptions,
                'fieldGroup' => $fieldDefinitionCreateStruct->fieldGroup ?? '',
                'position' => $fieldDefinitionCreateStruct->position ?? 0,
                'isTranslatable' => $fieldDefinitionCreateStruct->isTranslatable,
                'isThumbnail' => $fieldDefinitionCreateStruct->isThumbnail,
                'isRequired' => $fieldDefinitionCreateStruct->isRequired,
                'isInfoCollector' => $fieldDefinitionCreateStruct->isInfoCollector,
                'isSearchable' => $fieldDefinitionCreateStruct->isSearchable ?? $fieldType->isSearchable(),
                'mainLanguageCode' => $mainLanguageCode,
                // These properties are precreated in constructor
                //"fieldTypeConstraints"
                //"defaultValue"
            ]
        );

        $spiFieldDefinition->fieldTypeConstraints->validators = $fieldDefinitionCreateStruct->validatorConfiguration;
        $spiFieldDefinition->fieldTypeConstraints->fieldSettings = $fieldDefinitionCreateStruct->fieldSettings;
        $spiFieldDefinition->defaultValue = $fieldType->toPersistenceValue(
            $fieldType->acceptValue($fieldDefinitionCreateStruct->defaultValue)
        );

        return $spiFieldDefinition;
    }

    protected function getDateTime(int $timestamp): DateTime
    {
        // Instead of using DateTime(ts) we use setTimeStamp() so timezone does not get set to UTC
        $dateTime = new DateTime();
        $dateTime->setTimestamp($timestamp);

        return $dateTime;
    }
}
