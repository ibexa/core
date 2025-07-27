<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\MVC\Symfony\Component\Serializer;

use Ibexa\Core\MVC\Symfony\SiteAccess\Matcher\URIElement;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * @internal
 */
final class URIElementNormalizer implements NormalizerInterface, DenormalizerInterface
{
    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        return $data instanceof URIElement;
    }

    /**
     * @param \Ibexa\Core\MVC\Symfony\SiteAccess\Matcher\URIElement $data
     *
     * @return array{elementNumber: int, uriElements: array<string>}
     */
    public function normalize(
        mixed $data,
        ?string $format = null,
        array $context = []
    ): array {
        return [
            'type' => $data::class,
            'elementNumber' => $data->getElementNumber(),
            'uriElements' => $data->getUriElements(),
        ];
    }

    public function denormalize(mixed $data, string $type, ?string $format = null, array $context = []): URIElement
    {
        $uriElement = new URIElement($data['elementNumber']);
        if (!empty($data['uriElements'])) {
            $uriElement->setUriElements($data['uriElements']);
        }

        return $uriElement;
    }

    public function supportsDenormalization(
        mixed $data,
        string $type,
        ?string $format = null,
        array $context = []
    ): bool {
        return $type === URIElement::class;
    }

    public function getSupportedTypes(?string $format): array
    {
        return  [
            URIElement::class => true,
        ];
    }
}
