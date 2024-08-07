<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\Repository\Values\Content;

use Ibexa\Contracts\Core\Repository\Values\Content\ContentInfo as APIContentInfo;
use Ibexa\Contracts\Core\Repository\Values\Content\Location;
use Ibexa\Contracts\Core\Repository\Values\Content\TrashItem as APITrashItem;

/**
 * this class represents a trash item, which is actually a trashed location.
 *
 * @internal Meant for internal use by Repository, type hint against API object instead.
 */
class TrashItem extends APITrashItem
{
    /**
     * Content info of the content object of this trash item.
     *
     * @var \Ibexa\Contracts\Core\Repository\Values\Content\ContentInfo
     */
    protected $contentInfo;

    /** @var \Ibexa\Contracts\Core\Repository\Values\Content\Location */
    protected $parentLocation;

    /** @var array<int, int> */
    protected array $removedLocationContentIdMap = [];

    /**
     * Returns the content info of the content object of this trash item.
     *
     * @return \Ibexa\Contracts\Core\Repository\Values\Content\ContentInfo
     */
    public function getContentInfo(): APIContentInfo
    {
        return $this->contentInfo;
    }

    public function getParentLocation(): ?Location
    {
        return $this->parentLocation;
    }

    /**
     * @return array<int, int>
     */
    public function getRemovedLocationContentIdMap(): array
    {
        return $this->removedLocationContentIdMap;
    }

    /**
     * Function where list of properties are returned.
     *
     * Override to add dynamic properties
     *
     * @uses \parent::getProperties()
     *
     * @param array $dynamicProperties
     *
     * @return array
     */
    protected function getProperties($dynamicProperties = ['contentId', 'path'])
    {
        return parent::getProperties($dynamicProperties);
    }

    /**
     * Magic getter for retrieving convenience properties.
     *
     * @param string $property The name of the property to retrieve
     *
     * @return mixed
     */
    public function __get($property)
    {
        if ($property === 'contentId') {
            return $this->contentInfo->id;
        }

        return parent::__get($property);
    }

    /**
     * Magic isset for signaling existence of convenience properties.
     *
     * @param string $property
     *
     * @return bool
     */
    public function __isset($property)
    {
        if ($property === 'contentId') {
            return true;
        }

        return parent::__isset($property);
    }
}

class_alias(TrashItem::class, 'eZ\Publish\Core\Repository\Values\Content\TrashItem');
