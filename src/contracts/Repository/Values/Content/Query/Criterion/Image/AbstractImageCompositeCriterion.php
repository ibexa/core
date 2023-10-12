<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion\Image;

use Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion\CompositeCriterion;
use Ibexa\Core\Base\Exceptions\InvalidArgumentException;

/**
 * @template TImageCriteria of array
 *
 * @phpstan-type Range array{
 *      min?: int|null,
 *      max?: int|null,
 * }
 */
abstract class AbstractImageCompositeCriterion extends CompositeCriterion
{
    /**
     * @phpstan-param TImageCriteria $data
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException
     */
    public function __construct(
        string $fieldDefIdentifier,
        array $data
    ) {
        $this->validate($data, $this->getSupportedCriteria());

        $criteria = new Criterion\LogicalAnd(
            $this->buildCriteria($fieldDefIdentifier, $data)
        );

        parent::__construct($criteria);
    }

    /**
     * @phpstan-param TImageCriteria $data
     *
     * @return array<\Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion>
     */
    abstract protected function buildCriteria(string $fieldDefIdentifier, array $data): array;

    /**
     * @return array<string>
     */
    abstract protected function getSupportedCriteria(): array;

    /**
     * @phpstan-param TImageCriteria $data
     *
     * @param array<string> $supportedCriteria
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException
     */
    protected function validate(
        array $data,
        array $supportedCriteria
    ): void {
        if (empty($data)) {
            throw new InvalidArgumentException(
                '$data',
                sprintf(
                    'At least one of the supported criteria should be passed: "%s"',
                    implode(', ', $supportedCriteria)
                )
            );
        }

        $notSupportedCriteria = array_diff(
            array_keys($data),
            array_merge(
                $supportedCriteria,
                ['fieldDefIdentifier']
            )
        );

        if (!empty($notSupportedCriteria)) {
            throw new InvalidArgumentException(
                '$data',
                sprintf(
                    'Given criteria are not supported: "%s". Supported image criteria: "%s"',
                    implode(', ', $notSupportedCriteria),
                    implode(', ', $supportedCriteria)
                )
            );
        }
    }

    /**
     * @param array{min?: int|null} $data
     */
    protected function getMinValue(array $data): int
    {
        return $data['min'] ?? 0;
    }

    /**
     * @param array{max?: int|null} $data
     */
    protected function getMaxValue(array $data): ?int
    {
        return $data['max'] ?? null;
    }
}
