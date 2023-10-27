<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
namespace Ibexa\Bundle\Core\Converter;

use Ibexa\Contracts\Core\Repository\LocationService;
use Ibexa\Contracts\Core\Repository\Values\Content\Language;
use Ibexa\Contracts\Core\Repository\Values\Content\Location;
use Ibexa\Core\Helper\ContentPreviewHelper;

class LocationParamConverter extends RepositoryParamConverter
{
    /** @var \Ibexa\Contracts\Core\Repository\LocationService */
    private $locationService;

    private ContentPreviewHelper $contentPreviewHelper;

    public function __construct(
        LocationService $locationService,
        ContentPreviewHelper $contentPreviewHelper
    ) {
        $this->locationService = $locationService;
        $this->contentPreviewHelper = $contentPreviewHelper;
    }

    protected function getSupportedClass()
    {
        return Location::class;
    }

    protected function getPropertyName()
    {
        return 'locationId';
    }

    /**
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\UnauthorizedException
     */
    protected function loadValueObject($id): Location
    {
        $prioritizedLanguages = $this->contentPreviewHelper->isPreviewActive() ? Language::ALL : null;

        return $this->locationService->loadLocation($id, $prioritizedLanguages);
    }
}

class_alias(LocationParamConverter::class, 'eZ\Bundle\EzPublishCoreBundle\Converter\LocationParamConverter');
