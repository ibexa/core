<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Core\Persistence\Legacy\Content\Location;

use Ibexa\Contracts\Core\Persistence\Content\Location;
use Ibexa\Contracts\Core\Persistence\Content\Location\CreateStruct;

/**
 * Mapper for Location objects.
 */
class Mapper
{
    /**
     * Creates a Location from a $data row.
     *
     * $prefix can be used to define a table prefix for the location table.
     *
     * Optionally pass a Location object, which will be filled with the values.
     *
     * @param array $data
     * @param string $prefix
     * @param \Ibexa\Contracts\Core\Persistence\Content\Location|null $location
     *
     * @return \Ibexa\Contracts\Core\Persistence\Content\Location
     */
    public function createLocationFromRow(array $data, $prefix = '', ?Location $location = null)
    {
        $location = $location ?: new Location();

        $location->id = (int)$data[$prefix . 'node_id'];
        $location->priority = (int)$data[$prefix . 'priority'];
        $location->hidden = (bool)$data[$prefix . 'is_hidden'];
        $location->invisible = (bool)$data[$prefix . 'is_invisible'];
        $location->remoteId = $data[$prefix . 'remote_id'];
        $location->contentId = (int)$data[$prefix . 'contentobject_id'];
        $location->parentId = (int)$data[$prefix . 'parent_node_id'];
        $location->pathString = $data[$prefix . 'path_string'];
        $location->depth = (int)$data[$prefix . 'depth'];
        $location->sortField = (int)$data[$prefix . 'sort_field'];
        $location->sortOrder = (int)$data[$prefix . 'sort_order'];
        if (isset($data[$prefix . 'trashed'])) {
            $location->trashed = (int)$data[$prefix . 'trashed'];
        }

        return $location;
    }

    /**
     * Creates Location objects from the given $rows, optionally with key
     * $prefix.
     *
     * @param array $rows
     * @param string $prefix
     * @param \Ibexa\Contracts\Core\Persistence\Content\Location|null $location
     *
     * @return \Ibexa\Contracts\Core\Persistence\Content\Location[]
     */
    public function createLocationsFromRows(array $rows, $prefix = '', ?Location $location = null): array
    {
        $locations = [];

        foreach ($rows as $row) {
            $id = $row[$prefix . 'node_id'];
            if (!isset($locations[$id])) {
                $locations[$id] = $this->createLocationFromRow($row, $prefix, $location);
            }
        }

        return array_values($locations);
    }

    /**
     * Creates a Location CreateStruct from a $data row.
     *
     * @param array $data
     *
     * @return \Ibexa\Contracts\Core\Persistence\Content\Location\CreateStruct
     */
    public function getLocationCreateStruct(array $data)
    {
        $struct = new CreateStruct();

        $struct->contentId = $data['contentobject_id'];
        $struct->contentVersion = $data['contentobject_version'];
        $struct->hidden = $data['is_hidden'];
        $struct->invisible = $data['is_invisible'];
        $struct->mainLocationId = $data['main_node_id'];
        $struct->parentId = $data['parent_node_id'];
        $struct->priority = $data['priority'];
        $struct->remoteId = md5(uniqid(static::class, true));
        $struct->sortField = $data['sort_field'];
        $struct->sortOrder = $data['sort_order'];

        return $struct;
    }
}
