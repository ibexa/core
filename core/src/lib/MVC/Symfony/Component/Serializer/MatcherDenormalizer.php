<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\MVC\Symfony\Component\Serializer;

use Ibexa\Bundle\Core\SiteAccess\SiteAccessMatcherRegistryInterface;
use Ibexa\Core\MVC\Symfony\SiteAccess\Matcher;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\DenormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerAwareTrait;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

/**
 * @internal
 */
final class MatcherDenormalizer implements DenormalizerInterface, DenormalizerAwareInterface
{
    private const string MATCHER_NORMALIZER_ALREADY_WORKED = self::class . '_ALREADY_CALLED';

    use DenormalizerAwareTrait;

    private SiteAccessMatcherRegistryInterface $registry;

    public function __construct(SiteAccessMatcherRegistryInterface $registry)
    {
        $this->registry = $registry;
    }

    /**
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException
     * @throws \Symfony\Component\Serializer\Exception\ExceptionInterface
     */
    public function denormalize($data, string $type, ?string $format = null, array $context = []): object
    {
        $matcher = $this->registry->getMatcher($type);

        return $this->denormalizer->denormalize($data, $type, $format, $context + [
            AbstractNormalizer::OBJECT_TO_POPULATE => $matcher,
            self::MATCHER_NORMALIZER_ALREADY_WORKED => true,
        ]);
    }

    /**
     * @phpstan-param array<string, mixed> $context
     */
    public function supportsDenormalization($data, string $type, ?string $format = null, array $context = []): bool
    {
        if ($context[self::MATCHER_NORMALIZER_ALREADY_WORKED] ?? false) {
            return false;
        }

        return is_subclass_of($type, Matcher::class) && $this->registry->hasMatcher($type);
    }

    public function getSupportedTypes(?string $format): array
    {
        return [
            Matcher::class => false,
        ];
    }
}
