<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\FieldType\ValueSerializer;

use Ibexa\Contracts\Core\FieldType\Value;
use Ibexa\Contracts\Core\FieldType\ValueSerializerInterface;
use Symfony\Component\Serializer\Encoder\DecoderInterface;
use Symfony\Component\Serializer\Encoder\EncoderInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * Adapter for Symfony Serializer component.
 */
final class SymfonySerializerAdapter implements ValueSerializerInterface
{
    private const DEFAULT_FORMAT = 'json';

    /** @var NormalizerInterface */
    private $normalizer;

    /** @var DenormalizerInterface */
    private $denormalizer;

    /** @var EncoderInterface */
    private $encoder;

    /** @var DecoderInterface */
    private $decoder;

    /** @var string */
    private $format;

    /**
     * @param NormalizerInterface $normalizer
     * @param DenormalizerInterface $denormalizer
     * @param EncoderInterface $encoder
     * @param DecoderInterface $decoder
     * @param string $format
     */
    public function __construct(
        NormalizerInterface $normalizer,
        DenormalizerInterface $denormalizer,
        EncoderInterface $encoder,
        DecoderInterface $decoder,
        string $format = self::DEFAULT_FORMAT
    ) {
        $this->normalizer = $normalizer;
        $this->denormalizer = $denormalizer;
        $this->encoder = $encoder;
        $this->decoder = $decoder;
        $this->format = $format;
    }

    public function normalize(
        Value $value,
        array $context = []
    ): ?array {
        return $this->normalizer->normalize($value, $this->format, $context);
    }

    public function denormalize(
        ?array $data,
        string $valueClass,
        array $context = []
    ): Value {
        return $this->denormalizer->denormalize($data, $valueClass, $this->format, $context);
    }

    public function encode(
        ?array $data,
        array $context = []
    ): ?string {
        return $this->encoder->encode($data, $this->format, $context);
    }

    public function decode(
        ?string $data,
        array $context = []
    ): ?array {
        return $this->decoder->decode($data, $this->format, $context);
    }
}
