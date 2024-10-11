<?php

namespace Ibexa\Core\MVC\Symfony\Component\Serializer;

use Ibexa\Bundle\Core\SiteAccess\SiteAccessMatcherRegistryInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\ContextAwareDenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerAwareTrait;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

class MatcherDenormalizer implements DenormalizerInterface, DenormalizerAwareInterface, ContextAwareDenormalizerInterface
{
    private const MATCHER_NORMALIZER_ALREADY_WORKED = self::class . '_ALREADY_CALLED';

    use DenormalizerAwareTrait;

    private SiteAccessMatcherRegistryInterface $registry;

    public function __construct(SiteAccessMatcherRegistryInterface $registry)
    {
        $this->registry = $registry;
    }

    public function denormalize($data, string $type, ?string $format = null, array $context = []): object
    {
        $matcher = $this->registry->getMatcher($type);

        return $this->denormalizer->denormalize($data, $type, $format, $context + [
            AbstractNormalizer::OBJECT_TO_POPULATE => $matcher,
            self::MATCHER_NORMALIZER_ALREADY_WORKED => true,
        ]);
    }

    public function supportsDenormalization($data, string $type, ?string $format = null, array $context = []): bool
    {
        if ($context[self::MATCHER_NORMALIZER_ALREADY_WORKED] ?? false) {
            return false;
        }

        return $this->registry->hasMatcher($type);
    }
}
