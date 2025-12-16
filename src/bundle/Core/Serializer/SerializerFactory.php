<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Bundle\Core\Serializer;

use Symfony\Component\Serializer\Serializer;
use Traversable;

final class SerializerFactory
{
    /**
     * @var iterable<\Symfony\Component\Serializer\Normalizer\NormalizerInterface|\Symfony\Component\Serializer\Normalizer\DenormalizerInterface>
     */
    private iterable $normalizers;

    /**
     * @var iterable<\Symfony\Component\Serializer\Encoder\EncoderInterface|\Symfony\Component\Serializer\Encoder\DecoderInterface>
     */
    private iterable $encoders;

    /**
     * @param iterable<\Symfony\Component\Serializer\Normalizer\NormalizerInterface|\Symfony\Component\Serializer\Normalizer\DenormalizerInterface> $normalizers
     * @param iterable<\Symfony\Component\Serializer\Encoder\EncoderInterface|\Symfony\Component\Serializer\Encoder\DecoderInterface>               $encoders
     */
    public function __construct(iterable $normalizers, iterable $encoders)
    {
        $this->normalizers = $normalizers;
        $this->encoders = $encoders;
    }

    public function create(): Serializer
    {
        $normalizers = $this->normalizers;
        if ($normalizers instanceof Traversable) {
            $normalizers = iterator_to_array($normalizers);
        }

        $encoders = $this->encoders;
        if ($encoders instanceof Traversable) {
            $encoders = iterator_to_array($encoders);
        }

        return new Serializer($normalizers, $encoders);
    }
}
