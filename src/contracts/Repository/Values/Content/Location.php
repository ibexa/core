<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\Core\Repository\Values\Content;

use Ibexa\Contracts\Core\Repository\Exceptions\NotImplementedException;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\SortClause;
use Ibexa\Contracts\Core\Repository\Values\ValueObject;

/**
 * This class represents a location in the repository.
 *
 * @property-read \Ibexa\Contracts\Core\Repository\Values\Content\ContentInfo $contentInfo Calls {@see Location::getContentInfo()}
 * @property-read int $contentId Accessing magic getter is deprecated since 4.6.7 and will be removed in 5.0.0. Use {@see Location::getContentId()} instead.
 * @property-read int $id Accessing magic getter is deprecated since 4.6.7 and will be removed in 5.0.0. Use {@see Location::getId()} instead.
 * @property-read int $priority Position of the Location among its siblings when sorted using priority
 * @property-read bool $hidden Accessing magic getter is deprecated since 4.6.7 and will be removed in 5.0.0. Use {@see Location::isHidden()} instead.
 * @property-read bool $invisible Accessing magic getter is deprecated since 4.6.7 and will be removed in 5.0.0. Use {@see Location::isInvisible()} instead.
 * @property-read bool $explicitlyHidden Indicates that the Location entity has been explicitly marked as hidden.
 * @property-read string $remoteId A global unique ID of the content object
 * @property-read int $parentLocationId The ID of the parent location
 * @property-read string $pathString Accessing magic getter is deprecated since 4.6.7 and will be removed in 5.0.0. Use {@see Location::getPathString()} instead.
 * @property-read array $path Accessing magic getter is deprecated since 4.6.7 and will be removed in 5.0.0. Use {@see Location::getPath()} instead.
 * @property-read int $depth Accessing magic getter is deprecated since 4.6.7 and will be removed in 5.0.0. Use {@see Location::getDepth()} instead.
 * @property-read int $sortField Specifies which property the child locations should be sorted on. Valid values are found at {@link Location::SORT_FIELD_*}
 * @property-read int $sortOrder Specifies whether the sort order should be ascending or descending. Valid values are {@link Location::SORT_ORDER_*}
 */
abstract class Location extends ValueObject
{
    // @todo Rename these to better fit current naming, also reuse these in Persistence or copy the change over.
    public const int SORT_FIELD_PATH = 1;
    public const int SORT_FIELD_PUBLISHED = 2;
    public const int SORT_FIELD_MODIFIED = 3;
    public const int SORT_FIELD_SECTION = 4;
    public const int SORT_FIELD_DEPTH = 5;
    public const int SORT_FIELD_CLASS_IDENTIFIER = 6;
    public const int SORT_FIELD_CLASS_NAME = 7;
    public const int SORT_FIELD_PRIORITY = 8;
    public const int SORT_FIELD_NAME = 9;

    public const int SORT_FIELD_NODE_ID = 11;
    public const int SORT_FIELD_CONTENTOBJECT_ID = 12;

    public const int SORT_ORDER_DESC = 0;
    public const int SORT_ORDER_ASC = 1;

    public const int STATUS_DRAFT = 0;
    public const int STATUS_PUBLISHED = 1;

    /**
     * Map for Location sort fields to their respective SortClauses.
     *
     * Those not here (class name/identifier and modified subnode) are
     * missing/deprecated and will most likely be removed in the future.
     */
    public const array SORT_FIELD_MAP = [
        self::SORT_FIELD_PATH => SortClause\Location\Path::class,
        self::SORT_FIELD_PUBLISHED => SortClause\DatePublished::class,
        self::SORT_FIELD_MODIFIED => SortClause\DateModified::class,
        self::SORT_FIELD_SECTION => SortClause\SectionIdentifier::class,
        self::SORT_FIELD_DEPTH => SortClause\Location\Depth::class,
        self::SORT_FIELD_PRIORITY => SortClause\Location\Priority::class,
        self::SORT_FIELD_NAME => SortClause\ContentName::class,
        self::SORT_FIELD_NODE_ID => SortClause\Location\Id::class,
        self::SORT_FIELD_CONTENTOBJECT_ID => SortClause\ContentId::class,
    ];

    /**
     * Map for Location sort order to their respective Query SORT constants.
     */
    public const array SORT_ORDER_MAP = [
        self::SORT_ORDER_DESC => Query::SORT_DESC,
        self::SORT_ORDER_ASC => Query::SORT_ASC,
    ];

    protected int $id;

    /**
     * The status of the location.
     *
     * A location gets the status {@see Location::STATUS_DRAFT} on newly created content which is not published.
     * When content is published the location gets the status {@see Location::STATUS_PUBLISHED}.
     */
    public int $status = self::STATUS_PUBLISHED;

    /**
     * Location priority.
     *
     * Position of the Location among its siblings when sorted using priority
     * sort order.
     */
    protected int $priority;

    /**
     * Indicates that the Location entity is hidden (explicitly or hidden by content).
     */
    protected bool $hidden;

    /**
     * Indicates that the Location is not visible, being either marked as hidden itself,
     * or implicitly hidden by its Content or an ancestor Location.
     */
    protected bool $invisible;

    /**
     * Indicates that the Location entity has been explicitly marked as hidden.
     */
    protected bool $explicitlyHidden;

    /**
     * Remote ID.
     *
     * A universally unique identifier.
     */
    protected string $remoteId;

    /**
     * Parent ID.
     */
    protected int $parentLocationId;

    /**
     * The materialized path of the location entry, eg: /1/2/4/23/.
     */
    protected string $pathString;

    /**
     * The list of ancestor locations' IDs, ordered by increasing depth,
     * starting with '1', and ending with the current Location's ID.
     *
     * Same as {@see Location::$pathString} but as array, e.g.: `['1', '2', '4', '23']`.
     *
     * @var array<int, string>
     */
    protected array $path;

    /**
     * Depth location has in the location tree.
     */
    protected int $depth;

    /**
     * Specifies which property the child locations should be sorted on.
     *
     * Valid values are found at {@link Location::SORT_FIELD_*}
     */
    protected int $sortField;

    /**
     * Specifies whether the sort order should be ascending or descending.
     *
     * Valid values are {@link Location::SORT_ORDER_*}
     */
    protected int $sortOrder;

    protected Content $content;

    /**
     * Returns the content info of the content object of this location.
     *
     * @return \Ibexa\Contracts\Core\Repository\Values\Content\ContentInfo
     */
    abstract public function getContentInfo(): ContentInfo;

    /**
     * Return the parent location of this location.
     *
     * @return \Ibexa\Contracts\Core\Repository\Values\Content\Location|null
     */
    abstract public function getParentLocation(): ?Location;

    public function getId(): int
    {
        return $this->id;
    }

    public function getContentId(): int
    {
        return $this->getContentInfo()->getId();
    }

    /**
     * Returns true if current location is a draft.
     *
     * @return bool
     */
    public function isDraft(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    /**
     * @return \Ibexa\Contracts\Core\Repository\Values\Content\Content
     */
    public function getContent(): Content
    {
        return $this->content;
    }

    /**
     * Get SortClause objects built from Locations' sort options.
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotImplementedException If sort field has a deprecated/unsupported value which does not have a Sort Clause.
     *
     * @return \Ibexa\Contracts\Core\Repository\Values\Content\Query\SortClause[]
     */
    public function getSortClauses(): array
    {
        $map = self::SORT_FIELD_MAP;
        if (!isset($map[$this->sortField])) {
            throw new NotImplementedException(
                "Sort Clause not implemented for Location sort Field {$this->sortField}"
            );
        }

        $sortClause = new $map[$this->sortField]();
        $sortClause->direction = self::SORT_ORDER_MAP[$this->sortOrder];

        return [$sortClause];
    }

    /**
     * The path to the Location represented by the current instance,
     * e.g. /1/2/4/23/ where 23 is current ID.
     */
    public function getPathString(): string
    {
        return $this->pathString;
    }

    /**
     * The list of ancestor locations' IDs, ordered by increasing depth,
     * starting with 1, and ending with the current Location's ID.
     *
     * Same as {@see Location::getPathString()} but as array, e.g.: `['1', '2', '4', '23']`.
     *
     * @return array<int, string>
     */
    public function getPath(): array
    {
        if (isset($this->path)) {
            return $this->path;
        }

        $pathString = trim($this->pathString ?? '', '/');

        return $this->path = !empty($pathString) ? explode('/', $pathString) : [];
    }

    /**
     * Indicates that the Location is not visible, being either marked as hidden itself, or implicitly hidden by
     * its Content or an ancestor Location.
     */
    public function isInvisible(): bool
    {
        return $this->invisible;
    }

    /**
     * Indicates that the Location is hidden either explicitly or by content.
     */
    public function isHidden(): bool
    {
        return $this->hidden;
    }

    public function getDepth(): int
    {
        return $this->depth;
    }

    /** @deprecated 4.6.7 accessing magic getter is deprecated and will be removed in 5.0.0. */
    public function __isset($property)
    {
        if ($property === 'path') {
            return true;
        }

        return parent::__isset($property);
    }

    /** @deprecated 4.6.7 accessing magic getter is deprecated and will be removed in 5.0.0. */
    public function __get($property)
    {
        if ($property === 'path') {
            return $this->getPath();
        }

        return parent::__get($property);
    }
}
