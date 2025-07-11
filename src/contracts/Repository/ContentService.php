<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\Core\Repository;

use Ibexa\Contracts\Core\Repository\Values\Content\Content;
use Ibexa\Contracts\Core\Repository\Values\Content\ContentCreateStruct;
use Ibexa\Contracts\Core\Repository\Values\Content\ContentDraftList;
use Ibexa\Contracts\Core\Repository\Values\Content\ContentInfo;
use Ibexa\Contracts\Core\Repository\Values\Content\ContentList;
use Ibexa\Contracts\Core\Repository\Values\Content\ContentMetadataUpdateStruct;
use Ibexa\Contracts\Core\Repository\Values\Content\ContentUpdateStruct;
use Ibexa\Contracts\Core\Repository\Values\Content\Language;
use Ibexa\Contracts\Core\Repository\Values\Content\LocationCreateStruct;
use Ibexa\Contracts\Core\Repository\Values\Content\Relation;
use Ibexa\Contracts\Core\Repository\Values\Content\RelationList;
use Ibexa\Contracts\Core\Repository\Values\Content\RelationType;
use Ibexa\Contracts\Core\Repository\Values\Content\VersionInfo;
use Ibexa\Contracts\Core\Repository\Values\ContentType\ContentType;
use Ibexa\Contracts\Core\Repository\Values\Filter\Filter;
use Ibexa\Contracts\Core\Repository\Values\User\User;
use Ibexa\Contracts\Core\Repository\Values\ValueObject;

/**
 * This class provides service methods for managing content.
 *
 * @phpstan-type TFilteringLanguageFilter array<int, string>
 */
interface ContentService
{
    public const DEFAULT_PAGE_SIZE = 25;

    /**
     * Loads a content info object.
     *
     * To load fields use loadContent
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\UnauthorizedException if the user is not allowed to read the content.
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException if the content with the given id doesn't exist.
     */
    public function loadContentInfo(int $contentId): ContentInfo;

    /**
     * Bulk-load ContentInfo items by id's.
     *
     * Note: It doesn't throw exceptions on load, just skips erroneous (NotFound or Unauthorized) ContentInfo items.
     *
     * @param array<int, int> $contentIds
     *
     * @return array<int, \Ibexa\Contracts\Core\Repository\Values\Content\ContentInfo> List of ContentInfo with content ids as keys
     */
    public function loadContentInfoList(array $contentIds): iterable;

    /**
     * Loads a content info object for the given remoteId.
     *
     * To load fields use loadContent
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\UnauthorizedException if the user is not allowed to read the content.
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException if the content with the given remote id doesn't exist.
     */
    public function loadContentInfoByRemoteId(string $remoteId): ContentInfo;

    /**
     * Loads a version info of the given content object.
     *
     * If no version number is given, the method returns the current version.
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException if the version with the given number doesn't exist.
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\UnauthorizedException if the user is not allowed to load this version.
     *
     * @param int|null $versionNo the version number. If not given the current version is returned.
     */
    public function loadVersionInfo(ContentInfo $contentInfo, ?int $versionNo = null): VersionInfo;

    /**
     * Loads a version info of the given content object id.
     *
     * If no version number is given, the method returns the current version.
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException if the version with the given number doesn't exist.
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\UnauthorizedException if the user is not allowed to load this version.
     *
     * @param int|null $versionNo the version number. If not given the current version is returned.
     */
    public function loadVersionInfoById(int $contentId, ?int $versionNo = null): VersionInfo;

    /**
     * Bulk-load VersionInfo items by the list of ContentInfo Value Objects.
     *
     * @param array<int, \Ibexa\Contracts\Core\Repository\Values\Content\ContentInfo> $contentInfoList
     *
     * @return array<int, \Ibexa\Contracts\Core\Repository\Values\Content\VersionInfo> List of VersionInfo items with Content Ids as keys
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\BadStateException
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException
     */
    public function loadVersionInfoListByContentInfo(array $contentInfoList): array;

    /**
     * Loads content in a version for the given content info object.
     *
     * If no version number is given, the method returns the current version
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException if version with the given number doesn't exist.
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\UnauthorizedException if the user is not allowed to load this version.
     *
     * @param array<int, string> $languages A language priority, filters returned fields and is used as prioritized language code on
     *                         returned value object. If not given all languages are returned.
     * @param int|null $versionNo The version number. If not given the current version is returned from $contentInfo.
     * @param bool $useAlwaysAvailable Add Main language to $languages if true (default) and if {@see ContentInfo::$alwaysAvailable} is true.
     */
    public function loadContentByContentInfo(ContentInfo $contentInfo, array $languages = null, ?int $versionNo = null, bool $useAlwaysAvailable = true): Content;

    /**
     * Loads content in the version given by version info.
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\UnauthorizedException if the user is not allowed to load this version.
     *
     * @param string[] $languages A language priority, filters returned fields and is used as prioritized language code on
     *                         returned value object. If not given all languages are returned.
     * @param bool $useAlwaysAvailable Add Main language to $languages if true (default) and if {@see ContentInfo::$alwaysAvailable} is true.
     */
    public function loadContentByVersionInfo(VersionInfo $versionInfo, array $languages = null, bool $useAlwaysAvailable = true): Content;

    /**
     * Loads content in a version of the given content object.
     *
     * If no version number is given, the method returns the current version
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException if the content or version with the given id and languages doesn't exist.
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\UnauthorizedException if the user lacks:
     *                         - `content/read` permission for published content, or
     *                         - `content/read` and `content/versionread` permissions for draft content.
     *
     * @param array<int, string> $languages A language priority, filters returned fields and is used as prioritized language code on
     *                         returned value object. If not given all languages are returned.
     * @param int|null $versionNo The version number. If not given the current version is returned.
     * @param bool $useAlwaysAvailable Add Main language to $languages if true (default) and if {@see ContentInfo::$alwaysAvailable} is true.
     */
    public function loadContent(int $contentId, array $languages = null, ?int $versionNo = null, bool $useAlwaysAvailable = true): Content;

    /**
     * Loads content in a version for the content object reference by the given remote id.
     *
     * If no version is given, the method returns the current version
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException if the content or version with the given remote id doesn't exist.
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\UnauthorizedException if the user lacks:
     *                         - `content/read` permission for published content, or
     *                         - `content/read` and `content/versionread` permissions for draft content.
     *
     * @param string[] $languages A language priority, filters returned fields and is used as prioritized language code on
     *                         returned value object. If not given all languages are returned.
     * @param int|null $versionNo the version number. If not given the current version is returned
     * @param bool $useAlwaysAvailable Add Main language to \$languages if true (default) and if {@see ContentInfo::$alwaysAvailable} is true.
     */
    public function loadContentByRemoteId(string $remoteId, array $languages = null, ?int $versionNo = null, bool $useAlwaysAvailable = true): Content;

    /**
     * Bulk-load Content items by the list of ContentInfo Value Objects.
     *
     * Note: it doesn't throw exceptions on load, just ignores erroneous Content item.
     * Moreover, since the method works on pre-loaded ContentInfo list, it is assumed that user is
     * allowed to access every Content on the list.
     *
     * @param array<int, \Ibexa\Contracts\Core\Repository\Values\Content\ContentInfo> $contentInfoList
     * @param array<int, string> $languages A language priority, filters returned fields and is used as prioritized language code on
     *                            returned value object. If not given all languages are returned.
     * @param bool $useAlwaysAvailable Add Main language to \$languages if true (default) and if {@see ContentInfo::$alwaysAvailable} is true,
     *                                 unless all languages have been asked for.
     *
     * @return array<int, \Ibexa\Contracts\Core\Repository\Values\Content\Content> List of Content items with Content Ids as keys
     */
    public function loadContentListByContentInfo(array $contentInfoList, array $languages = [], bool $useAlwaysAvailable = true): iterable;

    /**
     * Creates a new content draft assigned to the authenticated user.
     *
     * If a different userId is given in $contentCreateStruct it is assigned to the given user
     * but this required special rights for the authenticated user
     * (this is useful for content staging where the transfer process doesn't
     * have to authenticate with the user which created the content object in the source server).
     * The user has to publish the draft if it should be visible.
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\UnauthorizedException if the user is not allowed to create the content in the given location.
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException if there is a provided remote ID which exists in the system or multiple Locations
     *                                                                        are under the same parent or if the a field value is not accepted by the field type
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\ContentFieldValidationException if a field in the $contentCreateStruct is not valid.
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\ContentValidationException if a required field is missing or is set to an empty value.
     *
     * @param \Ibexa\Contracts\Core\Repository\Values\Content\LocationCreateStruct[] $locationCreateStructs An array of {@see \Ibexa\Contracts\Core\Repository\Values\Content\LocationCreateStruct} for each location parent under which a location should be created for the content.
     *                                                                                                While optional, it's highly recommended to use Locations for content as a lot of features in the system is usually tied to the tree structure (including default Role policies).
     * @param string[]|null $fieldIdentifiersToValidate List of field identifiers for partial validation or null
     *                      for case of full validation. Empty identifiers array is equal to no validation.
     *
     * @return \Ibexa\Contracts\Core\Repository\Values\Content\Content The newly created content draft.
     */
    public function createContent(ContentCreateStruct $contentCreateStruct, array $locationCreateStructs = [], ?array $fieldIdentifiersToValidate = null): Content;

    /**
     * Updates the metadata.
     *
     * To update fields, use {@see ContentService::updateContent()}.
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\UnauthorizedException if the user is not allowed to update the content metadata.
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException if the remoteId in $contentMetadataUpdateStruct is set but already exists.
     *
     * @return \Ibexa\Contracts\Core\Repository\Values\Content\Content The content with the updated attributes.
     */
    public function updateContentMetadata(ContentInfo $contentInfo, ContentMetadataUpdateStruct $contentMetadataUpdateStruct): Content;

    /**
     * Deletes a content object including all its versions and locations including their subtrees.
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\UnauthorizedException if the user is not allowed to delete the content (in one of the locations of the given content object).
     *
     * @return array<int, int> Affected Location IDs (List of Location IDs of the Content that was deleted).
     */
    public function deleteContent(ContentInfo $contentInfo): array;

    /**
     * Creates a draft from a published or archived version.
     *
     * If no version is given, the current published version is used.
     * 4.x: The draft is created with the initialLanguage code of the source version or if not present with the main language.
     * It can be changed on updating the version.
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\UnauthorizedException if the current user is not allowed to create the draft.
     *
     * @param \Ibexa\Contracts\Core\Repository\Values\User\User|null $creator Used as creator of the draft if given; otherwise uses current user.
     * @param \Ibexa\Contracts\Core\Repository\Values\Content\Language|null $language If not set the draft is created with the initialLanguage code of the source version or if not present with the main language.
     *
     * @return \Ibexa\Contracts\Core\Repository\Values\Content\Content The newly created content draft.
     */
    public function createContentDraft(
        ContentInfo $contentInfo,
        ?VersionInfo $versionInfo = null,
        ?User $creator = null,
        ?Language $language = null
    ): Content;

    /**
     * Counts drafts for a user.
     *
     * If no user is given the number of drafts for the authenticated user are returned.
     *
     * @param \Ibexa\Contracts\Core\Repository\Values\User\User $user The user to load drafts for, if defined, otherwise drafts for current user.
     *
     * @return int The number of drafts ({@see VersionInfo}) owned by the given user.
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException
     */
    public function countContentDrafts(?User $user = null): int;

    /**
     * Loads drafts for a user when content is not in the trash. The list is sorted by modification date.
     *
     * If no user is given the drafts for the authenticated user are returned.
     *
     * @since 7.5.5
     *
     * @param \Ibexa\Contracts\Core\Repository\Values\User\User|null $user The user to load drafts for, if defined; otherwise drafts for current user.
     */
    public function loadContentDraftList(?User $user = null, int $offset = 0, int $limit = -1): ContentDraftList;

    /**
     * Updates the fields of a draft.
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\UnauthorizedException if the user is not allowed to update this version.
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\BadStateException if the version is not a draft.
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\ContentFieldValidationException if a field in the $contentUpdateStruct is not valid.
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\ContentValidationException if a required field is set to an empty value.
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException if a field value is not accepted by the field type.
     *
     * @param array<int, string>|null $fieldIdentifiersToValidate List of field identifiers for partial validation or null
     *                      for case of full validation. Empty identifiers array is equal to no validation.
     *
     * @return \Ibexa\Contracts\Core\Repository\Values\Content\Content The content draft with the updated fields.
     */
    public function updateContent(VersionInfo $versionInfo, ContentUpdateStruct $contentUpdateStruct, ?array $fieldIdentifiersToValidate = null): Content;

    /**
     * Publishes a content version.
     *
     * Publishes a content version and deletes archive versions if they overflow max archive versions.
     * Max archive versions are currently a configuration for default max limit, by default set to 5.
     *
     * @todo Introduce null|int ContentType->versionArchiveLimit to be able to let admins override this per type.
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\UnauthorizedException if the user is not allowed to publish this version.
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\BadStateException if the version is not a draft.
     *
     * @param array<int, string> $translations List of language codes of translations which will be included
     *                               in a published version.
     *                               By default, all translations from the current version will be published.
     *                               If the list is provided but doesn't cover all currently published translations,
     *                               the missing ones will be copied from the currently published version,
     *                               overriding those in the current version.
     */
    public function publishVersion(VersionInfo $versionInfo, array $translations = Language::ALL): Content;

    /**
     * Removes the given version.
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\BadStateException if the version is in
     *         published state or is a last version of Content in non-draft state.
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\UnauthorizedException if the user is not allowed to remove this version.
     */
    public function deleteVersion(VersionInfo $versionInfo): void;

    /**
     * Loads all versions for the given content.
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\UnauthorizedException if the user is not allowed to list versions.
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException if the given status is invalid.
     *
     * @return array<int, \Ibexa\Contracts\Core\Repository\Values\Content\VersionInfo> An array of {@see \Ibexa\Contracts\Core\Repository\Values\Content\VersionInfo} sorted by creation date.
     */
    public function loadVersions(ContentInfo $contentInfo, ?int $status = null): iterable;

    /**
     * Copies the content to a new location. If no version is given,
     * all versions are copied, otherwise only the given version.
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\UnauthorizedException if the user is not allowed to copy the content to the given location.
     *
     * @param \Ibexa\Contracts\Core\Repository\Values\Content\LocationCreateStruct $destinationLocationCreateStruct The target location where the content is copied to.
     */
    public function copyContent(ContentInfo $contentInfo, LocationCreateStruct $destinationLocationCreateStruct, ?VersionInfo $versionInfo = null): Content;

    /**
     * Loads all outgoing relations for the given version.
     *
     * If the user is not allowed to read specific version then a returned `RelationList` will contain `UnauthorizedRelationListItem`
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\BadStateException
     *
     * @see \Ibexa\Contracts\Core\Repository\Values\Content\RelationList\Item\UnauthorizedRelationListItem
     */
    public function loadRelationList(
        VersionInfo $versionInfo,
        int $offset = 0,
        int $limit = self::DEFAULT_PAGE_SIZE,
        ?RelationType $type = null,
    ): RelationList;

    /**
     * Counts all outgoing relations for the given version.
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\BadStateException
     */
    public function countRelations(VersionInfo $versionInfo, ?RelationType $type = null): int;

    /**
     * Counts all incoming relations for the given content object.
     *
     * @return int The number of reverse relations ({@see Relation}).
     */
    public function countReverseRelations(ContentInfo $contentInfo, ?RelationType $type = null): int;

    /**
     * Loads all incoming relations for a content object.
     *
     * The relations come only from published versions of the source content objects
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\UnauthorizedException if the user is not allowed to read this version.
     *
     * @return array<int, \Ibexa\Contracts\Core\Repository\Values\Content\Relation>
     */
    public function loadReverseRelations(ContentInfo $contentInfo, ?RelationType $type = null): iterable;

    /**
     * Loads all incoming relations for a content object.
     *
     * The relations come only from published versions of the source content objects.
     * If the user is not allowed to read specific version then {@see \Ibexa\Contracts\Core\Repository\Values\Content\RelationList\Item\UnauthorizedRelationListItem} is returned
     */
    public function loadReverseRelationList(
        ContentInfo $contentInfo,
        int $offset = 0,
        int $limit = -1,
        ?RelationType $type = null
    ): RelationList;

    /**
     * Adds a common relation.
     *
     * The source of the relation is the content and version
     * referenced by $sourceVersion.
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\UnauthorizedException if the user is not allowed to edit this version.
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\BadStateException if the version is not a draft.
     *
     * @param \Ibexa\Contracts\Core\Repository\Values\Content\VersionInfo $sourceVersion The source content's version in relation with the destination.
     * @param \Ibexa\Contracts\Core\Repository\Values\Content\ContentInfo $destinationContent The destination of the relation.
     *
     * @return \Ibexa\Contracts\Core\Repository\Values\Content\Relation The newly created relation.
     *
     * @see Relation::COMMON
     */
    public function addRelation(VersionInfo $sourceVersion, ContentInfo $destinationContent): Relation;

    /**
     * Removes a common relation from a draft.
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\UnauthorizedException if the user is not allowed edit this version.
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\BadStateException if the version is not a draft.
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException if there is no relation of type {@see Relation::COMMON} for the given destination.
     *
     * @see Relation::COMMON
     */
    public function deleteRelation(VersionInfo $sourceVersion, ContentInfo $destinationContent): void;

    /**
     * Delete Content item Translation from all Versions (including archived ones) of a Content Object.
     *
     * NOTE: this operation is risky and permanent, so user interface should provide a warning before performing it.
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\BadStateException if the specified Translation
     *         is the Main Translation of a Content Item.
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\UnauthorizedException if the user is not allowed
     *         to delete the content (in one of the locations of the given Content Item).
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException if $languageCode argument
     *         is invalid for the given content.
     *
     * @since 6.13
     */
    public function deleteTranslation(ContentInfo $contentInfo, string $languageCode): void;

    /**
     * Delete specified Translation from a Content Draft.
     *
     * When using together with {@see ContentService::publishVersion()} method, make sure to not provide deleted translation
     * in translations array, as it is going to be copied again from published version.
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\BadStateException if the specified Translation
     *         is the only one the Content Draft has or it is the main Translation of a Content Object.
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\UnauthorizedException if the user is not allowed
     *         to edit the Content (in one of the locations of the given Content Object).
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException if languageCode argument
     *         is invalid for the given Draft.
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException if specified Version was not found.
     *
     * @param \Ibexa\Contracts\Core\Repository\Values\Content\VersionInfo $versionInfo Content Version Draft.
     * @param string $languageCode Language code of the Translation to be removed.
     *
     * @return \Ibexa\Contracts\Core\Repository\Values\Content\Content Content Draft without the specified Translation.
     *
     * @since 6.12
     */
    public function deleteTranslationFromDraft(VersionInfo $versionInfo, string $languageCode): Content;

    /**
     * Hides Content by making all the Locations appear hidden.
     *
     * It doesn't persist hidden state on Location object itself.
     *
     * Content hidden by this API can be revealed by {@see ContentService::revealContent()} API.
     *
     * @see ContentService::revealContent()
     */
    public function hideContent(ContentInfo $contentInfo): void;

    /**
     * Reveals Content hidden by hideContent API.
     *
     * Locations which were hidden before hiding Content will remain hidden.
     *
     * @see ContentService::hideContent()
     */
    public function revealContent(ContentInfo $contentInfo): void;

    /**
     * Instantiates a new content create struct object.
     *
     * {@see ContentCreateStruct::$alwaysAvailable} is set to the {@see ContentType::$defaultAlwaysAvailable}.
     */
    public function newContentCreateStruct(ContentType $contentType, string $mainLanguageCode): ContentCreateStruct;

    /**
     * Instantiates a new content meta data update struct.
     */
    public function newContentMetadataUpdateStruct(): ContentMetadataUpdateStruct;

    /**
     * Instantiates a new content update struct.
     */
    public function newContentUpdateStruct(): ContentUpdateStruct;

    /**
     * Validates given content related ValueObject returning field errors structure as a result.
     *
     * @param array $context Additional context parameters to be used by validators.
     * @param array<int, string>|null $fieldIdentifiersToValidate List of field identifiers for partial validation, or null
     *                      for case of full validation. Empty identifiers array is equal to no validation.
     *
     * @return array Validation errors grouped by field definition and language code, in format:
     *           $returnValue[string|int $fieldDefinitionId][string $languageCode] = $fieldErrors;
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException
     */
    public function validate(ValueObject $object, array $context, ?array $fieldIdentifiersToValidate = null): array;

    /**
     * Fetches Content items from the Repository filtered by the given conditions.
     *
     * @phpstan-param TFilteringLanguageFilter|null $languages A list of language codes to be added as additional constraints.
     *        If skipped, by default, unless SiteAccessAware layer has been disabled, languages set
     *        for a SiteAccess in a current context will be used.
     */
    public function find(Filter $filter, ?array $languages = null): ContentList;

    /**
     * Gets the total number of fetchable Content items.
     *
     * Counts total number of items returned by {@see ContentService::find()} with the same parameters.
     *
     * @phpstan-param TFilteringLanguageFilter|null $languages $languages A list of language codes to be added as additional constraints.
     *        If skipped, by default, unless SiteAccessAware layer has been disabled, languages set
     *        for a SiteAccess in a current context will be used.
     *
     * @phpstan-return int<0, max>
     */
    public function count(Filter $filter, ?array $languages = null): int;
}
