<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Core\Repository\Values\ContentType;

use Ibexa\Contracts\Core\Repository\Values\ContentType\ContentType as APIContentType;
use Ibexa\Contracts\Core\Repository\Values\ContentType\FieldDefinitionCollection as APIFieldDefinitionCollection;
use Ibexa\Core\Repository\Values\MultiLanguageDescriptionTrait;
use Ibexa\Core\Repository\Values\MultiLanguageNameTrait;
use Ibexa\Core\Repository\Values\MultiLanguageTrait;

/**
 * this class represents a content type value.
 *
 * @property-read \Ibexa\Contracts\Core\Repository\Values\ContentType\ContentTypeGroup[] $contentTypeGroups calls getContentTypeGroups
 * @property-read \Ibexa\Contracts\Core\Repository\Values\ContentType\FieldDefinitionCollection $fieldDefinitions calls getFieldDefinitions() or on access getFieldDefinition($fieldDefIdentifier)
 * @property-read mixed $id the id of the content type
 * @property-read int $status the status of the content type. One of ContentType::STATUS_DEFINED|ContentType::STATUS_DRAFT|ContentType::STATUS_MODIFIED
 * @property-read string $identifier the identifier of the content type
 * @property-read \DateTime $creationDate the date of the creation of this content type
 * @property-read \DateTime $modificationDate the date of the last modification of this content type
 * @property-read mixed $creatorId the user id of the creator of this content type
 * @property-read mixed $modifierId the user id of the user which has last modified this content type
 * @property-read string $remoteId a global unique id of the content object
 * @property-read string $urlAliasSchema URL alias schema. If nothing is provided, $nameSchema will be used instead.
 * @property-read string $nameSchema  The name schema.
 * @property-read bool $isContainer This flag hints to UIs if type may have children or not.
 * @property-read string $mainLanguageCode the main language of the content type names and description used for fallback.
 * @property-read bool $defaultAlwaysAvailable if an instance of a content type is created the always available flag is set by default this this value.
 * @property-read int $defaultSortField Specifies which property the child locations should be sorted on by default when created. Valid values are found at {@link Location::SORT_FIELD_*}
 * @property-read int $defaultSortOrder Specifies whether the sort order should be ascending or descending by default when created. Valid values are {@link Location::SORT_ORDER_*}
 *
 * @internal Meant for internal use by Repository, type hint against API object instead.
 */
class ContentType extends APIContentType
{
    use MultiLanguageTrait;
    use MultiLanguageNameTrait;
    use MultiLanguageDescriptionTrait;

    /**
     * Holds the collection of contenttypegroups the contenttype is assigned to.
     *
     * @var \Ibexa\Contracts\Core\Repository\Values\ContentType\ContentTypeGroup[]
     */
    protected $contentTypeGroups = [];

    /**
     * Contains the content type field definitions from this type.
     *
     * @var \Ibexa\Contracts\Core\Repository\Values\ContentType\FieldDefinitionCollection
     */
    protected $fieldDefinitions;

    public function __construct(array $data = [])
    {
        $this->fieldDefinitions = new FieldDefinitionCollection();

        parent::__construct($data);
    }

    /**
     * This method returns the content type groups this content type is assigned to.
     *
     * @return \Ibexa\Contracts\Core\Repository\Values\ContentType\ContentTypeGroup[]
     */
    public function getContentTypeGroups(): array
    {
        return $this->contentTypeGroups;
    }

    /**
     * This method returns the content type field definitions from this type.
     *
     * @return \Ibexa\Contracts\Core\Repository\Values\ContentType\FieldDefinitionCollection
     */
    public function getFieldDefinitions(): APIFieldDefinitionCollection
    {
        return $this->fieldDefinitions;
    }
}
