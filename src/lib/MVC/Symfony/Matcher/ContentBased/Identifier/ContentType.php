<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Core\MVC\Symfony\Matcher\ContentBased\Identifier;

use Ibexa\Contracts\Core\Repository\Values\Content\ContentInfo;
use Ibexa\Contracts\Core\Repository\Values\Content\Location;
use Ibexa\Core\MVC\Symfony\Matcher\ContentBased\MultipleValued;
use Ibexa\Core\MVC\Symfony\View\ContentValueView;
use Ibexa\Core\MVC\Symfony\View\View;

class ContentType extends MultipleValued
{
    /**
     * Checks if a Location object matches.
     *
     * @param Location $location
     *
     * @return bool
     */
    public function matchLocation(Location $location): bool
    {
        $contentType = $this->repository
            ->getContentTypeService()
            ->loadContentType($location->getContentInfo()->contentTypeId);

        return isset($this->values[$contentType->identifier]);
    }

    /**
     * Checks if a ContentInfo object matches.
     *
     * @param ContentInfo $contentInfo
     *
     * @return bool
     */
    public function matchContentInfo(ContentInfo $contentInfo): bool
    {
        $contentType = $this->repository
            ->getContentTypeService()
            ->loadContentType($contentInfo->contentTypeId);

        return isset($this->values[$contentType->identifier]);
    }

    public function match(View $view): bool
    {
        if (!$view instanceof ContentValueView) {
            return false;
        }

        $contentType = $this->repository
            ->getContentTypeService()
            ->loadContentType($view->getContent()->contentInfo->contentTypeId);

        return isset($this->values[$contentType->identifier]);
    }
}
