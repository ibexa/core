<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\MVC\Symfony\Component\Serializer;

use Ibexa\Core\MVC\Symfony\SiteAccess;
use Ibexa\Core\MVC\Symfony\SiteAccess\Matcher;
use Ibexa\Core\MVC\Symfony\SiteAccessGroup;
use Symfony\Component\Serializer\Normalizer\DenormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerAwareTrait;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\SerializerAwareInterface;
use Symfony\Component\Serializer\SerializerAwareTrait;

/**
 * @internal
 */
final class SiteAccessNormalizer implements DenormalizerInterface, DenormalizerAwareInterface, NormalizerInterface, SerializerAwareInterface
{
    use DenormalizerAwareTrait;
    use SerializerAwareTrait;

    public function denormalize(
        mixed $data,
        string $type,
        ?string $format = null,
        array $context = []
    ): object {
        // BC for SiteAccess being serialized/normalized using json_encode via \Ibexa\Bundle\Core\Fragment\SiteAccessSerializer
        $matcherType = $data['matcher']['type'] ?? $data['matcher'];
        $matcherData = $data['matcher']['data'] ?? $context['serialized_siteaccess_matcher'];

        return new SiteAccess(
            $data['name'],
            $data['matchingType'],
            $data['matcher'] !== null
                ? $this->serializer?->deserialize(
                    $matcherData,
                    $matcherType,
                    $format ?? 'json',
                    $context
                )
                : null,
            $data['provider'] ?? null,
            $this->denormalizer->denormalize($data['groups'] ?? [], SiteAccessGroup::class . '[]', $format, $context)
        );
    }

    /**
     * @phpstan-param array<string, mixed> $context
     */
    public function supportsDenormalization(
        mixed $data,
        string $type,
        ?string $format = null,
        array $context = []
    ): bool {
        return $type === SiteAccess::class;
    }

    public function supportsNormalization(
        mixed $data,
        ?string $format = null,
        array $context = []
    ): bool {
        return $data instanceof SiteAccess;
    }

    /**
     * @param SiteAccess $data
     *
     * @return array{
     *     name: string,
     *     matchingType: string,
     *     matcher: array{type: class-string<Matcher>, data: string|null}|null,
     *     provider: string|null,
     *     groups: array<SiteAccessGroup>
     * }
     */
    public function normalize(
        mixed $data,
        ?string $format = null,
        array $context = []
    ): array {
        $matcherData = null;
        if (is_object($data->matcher)) {
            $matcherData = [
                'type' => get_class($data->matcher),
                'data' => $this->serializer?->serialize($data->matcher, $format ?? 'json', $context),
            ];
        }

        return [
            'name' => $data->name,
            'matchingType' => $data->matchingType,
            'matcher' => $matcherData,
            'provider' => $data->provider,
            'groups' => $data->groups,
        ];
    }

    public function getSupportedTypes(?string $format): array
    {
        return [
            SiteAccess::class => true,
        ];
    }
}
