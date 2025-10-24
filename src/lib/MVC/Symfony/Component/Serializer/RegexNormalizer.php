<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\MVC\Symfony\Component\Serializer;

use Ibexa\Core\MVC\Symfony\SiteAccess\Matcher\Regex;
use Symfony\Component\Serializer\Exception\LogicException;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * @internal
 */
final class RegexNormalizer implements NormalizerInterface, DenormalizerInterface
{
    public function supportsNormalization(
        mixed $data,
        ?string $format = null,
        array $context = []
    ): bool {
        return $data instanceof Regex;
    }

    /**
     * @param Regex $data
     *
     * @return array{type: class-string, regex: string, itemNumber: int}
     */
    public function normalize(
        mixed $data,
        ?string $format = null,
        array $context = []
    ): array {
        return [
            'type' => $data::class,
            'regex' => $data->getRegex(),
            'itemNumber' => $data->getItemNumber(),
        ];
    }

    public function denormalize(
        mixed $data,
        string $type,
        ?string $format = null,
        array $context = []
    ): Regex {
        $mapMatcherType = $data['type'] ?? throw new LogicException('Unknown Regex matcher type');
        if (!is_a($mapMatcherType, Regex::class, true)) {
            throw new LogicException(sprintf('%s is not a subtype of %s', $mapMatcherType, Regex::class));
        }

        $regexMatcher = $data['type']($data['regex'], $data['itemNumber']);
        assert($regexMatcher instanceof Regex);

        return $regexMatcher;
    }

    public function supportsDenormalization(
        mixed $data,
        string $type,
        ?string $format = null,
        array $context = []
    ): bool {
        return is_a($type, Regex::class, true) && is_a($data['type'] ?? null, Regex::class, true);
    }

    public function getSupportedTypes(?string $format): array
    {
        return [
            // false: building specific instance of Regex class depends on its data, not a static type
            Regex::class => false,
        ];
    }
}
