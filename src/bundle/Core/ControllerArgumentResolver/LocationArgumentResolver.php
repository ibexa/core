<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Bundle\Core\ControllerArgumentResolver;

use Ibexa\Contracts\Core\Exception\InvalidArgumentException;
use Ibexa\Contracts\Core\Repository\LocationService;
use Ibexa\Contracts\Core\Repository\Values\Content\Location;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ArgumentValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;

/**
 * @internal
 */
final class LocationArgumentResolver implements ArgumentValueResolverInterface
{
    private LocationService $locationService;

    private const PARAMETER_LOCATION_ID = 'locationId';

    public function __construct(LocationService $locationService)
    {
        $this->locationService = $locationService;
    }

    public function supports(Request $request, ArgumentMetadata $argument): bool
    {
        return
            Location::class === $argument->getType()
            && !$request->attributes->has(self::PARAMETER_LOCATION_ID)
            && $request->query->has(self::PARAMETER_LOCATION_ID);
    }

    /**
     * @return iterable<\Ibexa\Contracts\Core\Repository\Values\Content\Location>
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\UnauthorizedException
     */
    public function resolve(Request $request, ArgumentMetadata $argument): iterable
    {
        $locationId = $request->query->get(self::PARAMETER_LOCATION_ID);
        if (!is_numeric($locationId)) {
            throw new InvalidArgumentException(
                'locationId',
                'Expected numeric type, ' . get_debug_type($locationId) . ' given.'
            );
        }

        yield $this->locationService->loadLocation((int)$locationId);
    }
}
