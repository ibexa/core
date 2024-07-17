<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\MVC\Symfony\Component\Serializer;

use Ibexa\Core\MVC\Symfony\SiteAccess\Matcher\Map;
use Symfony\Component\Serializer\Normalizer\PropertyNormalizer;

final class MapNormalizer extends PropertyNormalizer
{
    /**
     * @see \Ibexa\Core\MVC\Symfony\SiteAccess\Matcher\Map::__sleep
     *
     * @param \Ibexa\Core\MVC\Symfony\SiteAccess\Matcher\Map $object
     *
     * @return array{key: string, map: array{}, reverseMap: array{}}
     */
    public function normalize($object, string $format = null, array $context = []): array
    {
        return [
            'key' => $object->getMapKey(),
            'map' => [],
            'reverseMap' => [],
        ];
    }

    public function supportsNormalization($data, string $format = null): bool
    {
        return $data instanceof Map;
    }
}
