<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\MVC\Symfony\Component\Serializer;

use Ibexa\Core\MVC\Symfony\SiteAccess\Matcher\HostText;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * @internal
 */
final class HostTextNormalizer implements NormalizerInterface, DenormalizerInterface
{
    /**
     * @param \Ibexa\Core\MVC\Symfony\SiteAccess\Matcher\HostText  $data
     * @param array<string, mixed> $context
     *
     * @phpstan-return array{siteAccessesConfiguration: array{prefix?: string, suffix?: string}}
     */
    public function normalize(
        mixed $data,
        ?string $format = null,
        array $context = []
    ): array {
        return [
            'type' => $data::class,
            'siteAccessesConfiguration' => $data->getSiteAccessesConfiguration(),
        ];
    }

    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        return $data instanceof HostText;
    }

    public function denormalize(mixed $data, string $type, ?string $format = null, array $context = []): mixed
    {
        return new HostText($data['siteAccessesConfiguration'] ?? []);
    }

    public function supportsDenormalization(
        mixed $data,
        string $type,
        ?string $format = null,
        array $context = []
    ): bool {
        return $type === HostText::class;
    }

    public function getSupportedTypes(?string $format): array
    {
        return  [
            HostText::class => true,
        ];
    }
}
