<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\MVC\Symfony\Component\Serializer;

use Ibexa\Core\MVC\Symfony\SiteAccess\Matcher\HostElement;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * @internal
 */
final class HostElementNormalizer implements NormalizerInterface, DenormalizerInterface
{
    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        return $data instanceof HostElement;
    }

    /**
     * @param \Ibexa\Core\MVC\Symfony\SiteAccess\Matcher\HostElement $data
     *
     * @return array{elementNumber: int, hostElements: array<int, string>}
     */
    public function normalize(
        mixed $data,
        ?string $format = null,
        array $context = []
    ): array {
        return [
            'type' => $data::class,
            'elementNumber' => $data->getElementNumber(),
            'hostElements' => $data->getHostElements(),
        ];
    }

    public function getSupportedTypes(?string $format): array
    {
        return [
            HostElement::class => true,
        ];
    }

    public function denormalize(mixed $data, string $type, ?string $format = null, array $context = []): HostElement
    {
        $hostElement = new HostElement($data['elementNumber']);
        if (!empty($data['hostElements'])) {
            $hostElement->setHostElements($data['hostElements']);
        }

        return $hostElement;
    }

    public function supportsDenormalization(
        mixed $data,
        string $type,
        ?string $format = null,
        array $context = []
    ): bool {
        return $type === HostElement::class;
    }
}
