<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Bundle\Core\ValueResolver;

use Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException;
use Ibexa\Contracts\Core\Repository\Exceptions\UnauthorizedException;
use Ibexa\Contracts\Core\Repository\LocationService;
use Ibexa\Contracts\Core\Repository\Values\Content\Language;
use Ibexa\Contracts\Core\Repository\Values\Content\Location;
use Ibexa\Core\Helper\ContentPreviewHelper;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;

final class LocationValueResolver implements ValueResolverInterface
{
    private const string ATTRIBUTE_LOCATION_ID = 'locationId';

    public function __construct(
        private readonly LocationService $locationService,
        private readonly ContentPreviewHelper $contentPreviewHelper
    ) {}

    /**
     * @return iterable<Location>
     *
     * @throws NotFoundException
     * @throws UnauthorizedException
     */
    public function resolve(
        Request $request,
        ArgumentMetadata $argument
    ): iterable {
        if ($argument->getType() !== Location::class) {
            return [];
        }

        $locationId = $request->attributes->get(self::ATTRIBUTE_LOCATION_ID);

        if (!is_numeric($locationId)) {
            return [];
        }

        $prioritizedLanguages = $this->contentPreviewHelper->isPreviewActive() ? Language::ALL : null;

        yield $this->locationService->loadLocation((int)$locationId, $prioritizedLanguages);
    }
}
