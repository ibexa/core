<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Core\Helper;

use Ibexa\Contracts\Core\Persistence\Content\Location\Handler as PersistenceLocationHandler;
use Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException;
use Ibexa\Contracts\Core\Repository\Exceptions\UnauthorizedException;
use Ibexa\Contracts\Core\Repository\LocationService;
use Ibexa\Contracts\Core\Repository\Values\Content\Content as APIContent;
use Ibexa\Contracts\Core\Repository\Values\Content\Location as APILocation;
use Ibexa\Core\Repository\Values\Content\Location;
use Random\RandomException;

/**
 * Provides location(s) for a content. Handles unpublished content that does not have an actual location yet.
 */
class PreviewLocationProvider
{
    private LocationService $locationService;

    private PersistenceLocationHandler $locationHandler;

    public function __construct(
        LocationService $locationService,
        PersistenceLocationHandler $locationHandler
    ) {
        $this->locationService = $locationService;
        $this->locationHandler = $locationHandler;
    }

    /**
     * Loads the main location for $content.
     *
     * If the content does not have a location (yet), but has a Location draft, it is returned instead.
     * Location drafts do not have an id (it is set to null), and can be tested using the isDraft() method.
     *
     * If the content doesn't have a location nor a location draft, null is returned.
     *
     * @throws RandomException
     * @throws NotFoundException
     * @throws UnauthorizedException
     */
    public function loadMainLocationByContent(APIContent $content): ?APILocation
    {
        $location = null;
        $contentInfo = $content
            ->getVersionInfo()
            ->getContentInfo();

        // mainLocationId already exists, content has been published at least once.
        if ($contentInfo->mainLocationId) {
            $location = $this->locationService->loadLocation($contentInfo->mainLocationId);
        } elseif (!$contentInfo->published) {
            // New Content, never published, create a virtual location object.
            // In cases content is missing locations this will return empty array
            $parentLocations = $this->locationHandler->loadParentLocationsForDraftContent($contentInfo->id);
            if (empty($parentLocations)) {
                return null;
            }

            // @todo add support for draft locations
            $location = new Location(
                [
                    'id' => 0,
                    'content' => $content,
                    'contentInfo' => $contentInfo,
                    'status' => APILocation::STATUS_DRAFT,
                    'priority' => 0,
                    'remoteId' => bin2hex(random_bytes(16)), // Unique remoteId for the "draft" location (32 chars)
                    'parentLocationId' => $parentLocations[0]->id,
                    'depth' => $parentLocations[0]->depth + 1,
                    'pathString' => $parentLocations[0]->pathString . 'x/',
                    'sortField' => APILocation::SORT_FIELD_NAME,
                    'sortOrder' => APILocation::SORT_ORDER_ASC,
                ]
            );
        }

        return $location;
    }
}
