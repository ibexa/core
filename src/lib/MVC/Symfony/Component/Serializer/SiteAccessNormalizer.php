<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\MVC\Symfony\Component\Serializer;

use Ibexa\Core\MVC\Symfony\SiteAccess;
use Ibexa\Core\MVC\Symfony\SiteAccessGroup;
use Symfony\Component\Serializer\Normalizer\ContextAwareDenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerAwareTrait;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\SerializerAwareInterface;
use Symfony\Component\Serializer\SerializerAwareTrait;

/**
 * @internal
 */
final class SiteAccessNormalizer implements DenormalizerInterface, DenormalizerAwareInterface, NormalizerInterface, SerializerAwareInterface, ContextAwareDenormalizerInterface
{
    use DenormalizerAwareTrait;
    use SerializerAwareTrait;

    public function denormalize($data, string $type, ?string $format = null, array $context = []): object
    {
        // BC for SiteAccess being serialized/normalized using json_encode via \Ibexa\Bundle\Core\Fragment\SiteAccessSerializer
        $matcherType = $data['matcher']['type'] ?? $data['matcher'];
        $matcherData = $data['matcher']['data'] ?? $context['serialized_siteaccess_matcher'];

        return new SiteAccess(
            $data['name'],
            $data['matchingType'],
            $data['matcher'] !== null
                ? $this->serializer->deserialize(
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

    public function supportsDenormalization($data, string $type, ?string $format = null, array $context = []): bool
    {
        return $type === SiteAccess::class;
    }

    public function supportsNormalization($data, ?string $format = null): bool
    {
        return $data instanceof SiteAccess;
    }

    /**
     * @param \Ibexa\Core\MVC\Symfony\SiteAccess $object
     *
     * @return array{name: string, matchingType: string, matcher: array{type: class-string, data: string}|null, provider: string|null, groups: array<mixed>}
     */
    public function normalize($object, ?string $format = null, array $context = []): array
    {
        $matcherData = null;
        if (is_object($object->matcher)) {
            $matcherData = [
                'type' => get_class($object->matcher),
                'data' => $this->serializer->serialize($object->matcher, $format ?? 'json', $context),
            ];
        }

        return [
            'name' => $object->name,
            'matchingType' => $object->matchingType,
            'matcher' => $matcherData,
            'provider' => $object->provider,
            'groups' => $object->groups,
        ];
    }
}
