<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\MVC\Symfony\Component\Serializer;

use Symfony\Component\Serializer\Exception\LogicException;
use Symfony\Component\Serializer\Normalizer\PropertyNormalizer;

abstract class AbstractPropertyWhitelistNormalizer extends PropertyNormalizer
{
    /**
     * @return array<string, mixed>
     *
     * @throws \Symfony\Component\Serializer\Exception\ExceptionInterface
     */
    public function normalize($object, string $format = null, array $context = []): array
    {
        $data = parent::normalize($object, $format, $context) ?? [];
        if (!is_array($data)) {
            throw new LogicException(sprintf('Expected an array, got "%s"', gettype($data)));
        }

        foreach (array_keys(iterator_to_array($data)) as $property) {
            if (!in_array($property, $this->getAllowedProperties(), true)) {
                unset($data[$property]);
            }
        }

        return $data;
    }

    /**
     * @return string[]
     */
    abstract protected function getAllowedProperties(): array;
}
