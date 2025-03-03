<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Core\MVC\Symfony\Component\Serializer\Stubs;

use Ibexa\Contracts\Core\Repository\Exceptions\NotImplementedException;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\SerializerInterface;

final class SerializerStub implements SerializerInterface, NormalizerInterface
{
    /**
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotImplementedException
     */
    public function serialize(mixed $data, string $format, array $context = []): string
    {
        throw new NotImplementedException(__METHOD__);
    }

    /**
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotImplementedException
     */
    public function deserialize(mixed $data, string $type, string $format, array $context = []): mixed
    {
        throw new NotImplementedException(__METHOD__);
    }

    public function normalize($object, string $format = null, array $context = []): array|bool|string|int|float|null|\ArrayObject
    {
        if (is_array($object)) {
            $result = [];
            foreach ($object as $key => $value) {
                $result[$key] = $this->normalize($value, $format, $context);
            }

            return $result;
        }

        if ($object instanceof MatcherStub) {
            return [
                'data' => $object->getData(),
            ];
        }

        return $object;
    }

    public function supportsNormalization($data, string $format = null): bool
    {
        return $data instanceof MatcherStub;
    }
}
