<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\MVC\Symfony\Component\Serializer;

use Ibexa\Core\MVC\Symfony\SiteAccess\Matcher\Compound;
use Symfony\Component\Serializer\Exception\LogicException;
use Symfony\Component\Serializer\Normalizer\DenormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerAwareTrait;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareTrait;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * @internal
 *
 * @phpstan-type TNormalizedCompoundMatcherData array{type: class-string, subMatchers: array<mixed>, config: array<mixed>, matchersMap: array<mixed>}
 */
class CompoundMatcherNormalizer implements NormalizerInterface, NormalizerAwareInterface, DenormalizerInterface, DenormalizerAwareInterface
{
    use DenormalizerAwareTrait;
    use NormalizerAwareTrait;

    /**
     * @param \Ibexa\Core\MVC\Symfony\SiteAccess\Matcher\Compound $data
     *
     * @phpstan-return TNormalizedCompoundMatcherData $data
     *
     * @throws \Symfony\Component\Serializer\Exception\ExceptionInterface
     *
     * @see \Ibexa\Core\MVC\Symfony\SiteAccess\Matcher\Compound::__sleep
     */
    public function normalize(mixed $data, ?string $format = null, array $context = []): array
    {
        /** @var array<string, array<mixed>> $subMatchers */
        $subMatchers = $this->normalizer->normalize($data->getSubMatchers(), $format, $context);

        return [
            'type' => $data::class,
            'subMatchers' => $subMatchers,
            'config' => [],
            'matchersMap' => [],
        ];
    }

    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        return $data instanceof Compound;
    }

    public function supportsDenormalization(
        mixed $data,
        string $type,
        ?string $format = null,
        array $context = []
    ): bool {
        return is_a($type, Compound::class, true) && is_a($data['type'] ?? null, Compound::class, true);
    }

    /**
     * @phpstan-param class-string<\Ibexa\Core\MVC\Symfony\SiteAccess\Matcher\Compound> $type
     *
     * @throws \Symfony\Component\Serializer\Exception\ExceptionInterface
     */
    public function denormalize(mixed $data, string $type, ?string $format = null, array $context = []): object
    {
        $compoundMatcherType = $data['type'] ?? throw new LogicException('Unknown compound matcher type');
        if (!is_a($compoundMatcherType, Compound::class, true)) {
            throw new LogicException(sprintf('%s is not a subtype of %s', $compoundMatcherType, Compound::class));
        }

        $compoundMatcher = new $compoundMatcherType($data['config'] ?? []);
        $subMatchers = [];
        foreach ($data['subMatchers'] ?? [] as $matcherKey => $subMatcher) {
            $subMatcherClass = $subMatcher['type'];

            $subMatchers[$matcherKey] = $this->denormalizer->denormalize(
                $subMatcher,
                $subMatcherClass,
                $format,
                $context
            );
        }
        $compoundMatcher->setSubMatchers($subMatchers);

        return $compoundMatcher;
    }

    public function getSupportedTypes(?string $format): array
    {
        return [
            // don't cache it, as the normalizer relies on other things besides type
            Compound::class => false,
        ];
    }
}
