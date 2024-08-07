<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
namespace Ibexa\Contracts\Core\Persistence\Content\Location;

use Ibexa\Contracts\Core\Persistence\Content\Location;

/**
 * Struct containing accessible properties on TrashedLocation entities.
 */
class Trashed extends Location
{
    /**
     * Trashed timestamp.
     *
     * @var mixed Trashed timestamp.
     */
    public $trashed;

    /** @var array<int, int> Location ID to a Content ID map of removed items */
    public array $removedLocationContentIdMap = [];
}

class_alias(Trashed::class, 'eZ\Publish\SPI\Persistence\Content\Location\Trashed');
