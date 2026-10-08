<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Core\MVC\Symfony\Component\Serializer;

use Ibexa\Core\MVC\Symfony\SiteAccess\Matcher\Compound;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerAwareTrait;

class CompoundMatcherNormalizer extends AbstractPropertyWhitelistNormalizer implements DenormalizerAwareInterface
{
    use DenormalizerAwareTrait;

    /**
     * @param Compound $object
     *
     * @throws ExceptionInterface
     *
     * @see \Ibexa\Core\MVC\Symfony\SiteAccess\Matcher\Compound::__sleep.
     */
    public function normalize(
        $object,
        ?string $format = null,
        array $context = []
    ) {
        $data = parent::normalize($object, $format, $context);

        /** @var array<string, mixed> $data */
        $data['config'] = [];
        $data['matchersMap'] = [];

        return $data;
    }

    protected function getAllowedProperties(): array
    {
        return ['subMatchers'];
    }

    public function supportsNormalization(
        $data,
        ?string $format = null
    ): bool {
        return $data instanceof Compound;
    }

    public function supportsDenormalization(
        $data,
        string $type,
        ?string $format = null
    ): bool {
        return is_a($type, Compound::class, true);
    }

    /**
     * @phpstan-param class-string<Compound> $type
     *
     * @throws ExceptionInterface
     */
    public function denormalize(
        $data,
        string $type,
        ?string $format = null,
        array $context = []
    ): object {
        $compoundMatcher = new $type([]);
        $subMatchers = [];
        foreach ($context['serialized_siteaccess_sub_matchers'] ?? [] as $matcherType => $subMatcher) {
            $subMatchers[$matcherType] = $this->serializer->deserialize(
                $subMatcher,
                $matcherType,
                $format ?? 'json',
                $context
            );
        }
        $compoundMatcher->setSubMatchers($subMatchers);

        return $compoundMatcher;
    }
}

class_alias(CompoundMatcherNormalizer::class, 'eZ\Publish\Core\MVC\Symfony\Component\Serializer\CompoundMatcherNormalizer');
