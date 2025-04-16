<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Core\MVC\Symfony\Component\Serializer;

use Ibexa\Core\MVC\Symfony\Routing\SimplifiedRequest;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * @internal
 */
final class SimplifiedRequestNormalizer implements NormalizerInterface, DenormalizerInterface
{
    /**
     * @param \Ibexa\Core\MVC\Symfony\Routing\SimplifiedRequest $data
     *
     * @return array{
     *     scheme: ?string,
     *     host: ?string,
     *     port: ?int,
     *     pathInfo: ?string,
     *     queryParams: ?array<mixed>,
     *     languages: ?string[],
     *     headers: ?array{}
     * }
     *
     * @see \Symfony\Component\Serializer\Normalizer\NormalizerInterface::normalize
     */
    public function normalize(mixed $data, ?string $format = null, array $context = []): array
    {
        return [
            'scheme' => $data->getScheme(),
            'host' => $data->getHost(),
            'port' => $data->getPort(),
            'pathInfo' => $data->getPathInfo(),
            'queryParams' => $data->getQueryParams(),
            'languages' => $data->getLanguages(),
            'headers' => [],
        ];
    }

    public function supportsNormalization($data, $format = null, array $context = []): bool
    {
        return $data instanceof SimplifiedRequest;
    }

    public function denormalize(mixed $data, string $type, ?string $format = null, array $context = []): mixed
    {
        return new SimplifiedRequest(
            $data['scheme'] ?? null,
            $data['host'] ?? null,
            $data['port'] ?? null,
            $data['pathInfo'] ?? null,
            $data['queryParams'] ?? null,
            $data['languages'] ?? null,
            $data['headers'] ?? [],
        );
    }

    public function supportsDenormalization(
        mixed $data,
        string $type,
        ?string $format = null,
        array $context = []
    ): bool {
        return $type === SimplifiedRequest::class;
    }

    public function getSupportedTypes(?string $format): array
    {
        return [
            SimplifiedRequest::class => true,
        ];
    }
}
