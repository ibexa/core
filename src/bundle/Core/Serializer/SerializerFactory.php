<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Bundle\Core\Serializer;

use Symfony\Component\Serializer\Serializer;

final readonly class SerializerFactory
{
    /**
     * @param iterable<\Symfony\Component\Serializer\Normalizer\NormalizerInterface|\Symfony\Component\Serializer\Normalizer\DenormalizerInterface> $normalizers
     * @param iterable<\Symfony\Component\Serializer\Encoder\EncoderInterface|\Symfony\Component\Serializer\Encoder\DecoderInterface> $encoders
     */
    public function __construct(
        private iterable $normalizers,
        private iterable $encoders
    ) {
    }

    public function create(): Serializer
    {
        $normalizers = iterator_to_array($this->normalizers);
        $encoders = iterator_to_array($this->encoders);

        return new Serializer($normalizers, $encoders);
    }
}
