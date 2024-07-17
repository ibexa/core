<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Core\MVC\Symfony\Component\Serializer;

use Ibexa\Core\MVC\Symfony\Routing\SimplifiedRequest;
use Symfony\Component\Serializer\Normalizer\PropertyNormalizer;

final class SimplifiedRequestNormalizer extends PropertyNormalizer
{
    /**
     * @param \Ibexa\Core\MVC\Symfony\Routing\SimplifiedRequest $object
     *
     * @return array{
     *     scheme: string,
     *     host: string,
     *     port: string,
     *     pathinfo: string,
     *     queryParams: array<mixed>,
     *     languages: string[],
     *     headers: array{}
     * }
     *
     * @see \Symfony\Component\Serializer\Normalizer\NormalizerInterface::normalize
     */
    public function normalize($object, $format = null, array $context = []): array
    {
        return [
            'scheme' => $object->scheme,
            'host' => $object->host,
            'port' => $object->port,
            'pathinfo' => $object->pathinfo,
            'queryParams' => $object->queryParams,
            'languages' => $object->languages,
            'headers' => [],
        ];
    }

    public function supportsNormalization($data, $format = null, array $context = []): bool
    {
        return $data instanceof SimplifiedRequest;
    }
}
