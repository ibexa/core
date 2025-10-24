<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\MVC\Symfony\Component\Serializer;

use Ibexa\Core\MVC\Symfony\SiteAccess\Matcher\Map;
use Symfony\Component\Serializer\Exception\LogicException;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * @internal
 */
final class MapNormalizer implements NormalizerInterface, DenormalizerInterface
{
    /**
     * @param Map $data
     *
     * @return array{
     *     type: class-string<Map>,
     *     key: string|null,
     *     map: array{},
     *     reverseMap: array{}
     * }
     *
     * @see Map::__sleep
     */
    public function normalize(
        mixed $data,
        ?string $format = null,
        array $context = []
    ): array {
        return [
            'type' => $data::class,
            'key' => $data->getMapKey(),
            'map' => [],
            'reverseMap' => [],
        ];
    }

    public function supportsNormalization(
        mixed $data,
        ?string $format = null,
        array $context = []
    ): bool {
        return $data instanceof Map;
    }

    public function denormalize(
        mixed $data,
        string $type,
        ?string $format = null,
        array $context = []
    ): Map {
        $mapMatcherType = $data['type'] ?? throw new LogicException('Unknown Map matcher type');
        if (!is_a($mapMatcherType, Map::class, true)) {
            throw new LogicException(sprintf('%s is not a subtype of %s', $mapMatcherType, Map::class));
        }

        $mapMatcher = new $mapMatcherType($data['map'] ?? []);
        if (isset($data['key'])) {
            $mapMatcher->setMapKey($data['key']);
        }

        return $mapMatcher;
    }

    public function supportsDenormalization(
        mixed $data,
        string $type,
        ?string $format = null,
        array $context = []
    ): bool {
        return is_a($type, Map::class, true) && is_a($data['type'] ?? null, Map::class, true);
    }

    public function getSupportedTypes(?string $format): array
    {
        return [
            Map::class => true,
        ];
    }
}
