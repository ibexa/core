<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\Repository\Values\Content;

use Ibexa\Contracts\Core\Repository\Values\Content\ContentInfo as APIContentInfo;
use Ibexa\Contracts\Core\Repository\Values\Content\Location as APILocation;

/**
 * This class represents a location in the repository.
 *
 * @internal Meant for internal use by Repository, type hint against API object instead.
 */
class Location extends APILocation
{
    protected APIContentInfo $contentInfo;

    protected ?APILocation $parentLocation;

    /**
     * Returns the content info of the location's content item.
     *
     * @return \Ibexa\Contracts\Core\Repository\Values\Content\ContentInfo
     */
    public function getContentInfo(): APIContentInfo
    {
        return $this->contentInfo;
    }

    public function getParentLocation(): ?APILocation
    {
        return $this->parentLocation;
    }

    /**
     * Overridden to add dynamic properties.
     *
     * @uses \parent::getProperties()
     *
     * @param string[] $dynamicProperties
     *
     * @return string[]
     */
    protected function getProperties($dynamicProperties = ['contentId']): array
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
            return $this->getContentInfo()->getId();
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
