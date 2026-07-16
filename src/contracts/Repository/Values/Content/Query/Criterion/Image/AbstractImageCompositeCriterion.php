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
 *      min?: numeric|null,
 *      max?: numeric|null,
 * }
 */
abstract class AbstractImageCompositeCriterion extends CompositeCriterion
{
    /**
     * @phpstan-param TImageCriteria $imageCriteriaData
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException
     */
    public function __construct(
        string $fieldDefIdentifier,
        array $imageCriteriaData
    ) {
        $this->validate($imageCriteriaData, $this->getSupportedCriteria());

        $criteria = new Criterion\LogicalAnd(
            $this->buildCriteria($fieldDefIdentifier, $imageCriteriaData)
        );

        parent::__construct($criteria);
    }

    /**
     * @phpstan-param TImageCriteria $imageCriteriaData
     *
     * @return array<Criterion>
     */
    abstract protected function buildCriteria(
        string $fieldDefIdentifier,
        array $imageCriteriaData
    ): array;

    /**
     * @return array<string>
     */
    abstract protected function getSupportedCriteria(): array;

    /**
     * @phpstan-param TImageCriteria $imageCriteriaData
     *
     * @param array<string> $supportedCriteria
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException
     */
    protected function validate(
        array $imageCriteriaData,
        array $supportedCriteria
    ): void {
        if (empty($imageCriteriaData)) {
            throw new InvalidArgumentException(
                '$data',
                sprintf(
                    'At least one of the supported criteria should be passed: "%s"',
                    implode(', ', $supportedCriteria)
                )
            );
        }

        $notSupportedCriteria = array_diff(
            array_keys($imageCriteriaData),
            $supportedCriteria
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
     * @phpstan-param array{min?: numeric|null} $data
     *
     * @phpstan-return numeric
     */
    protected function getMinValue(array $data): int | float | string
    {
        return $data['min'] ?? 0;
    }

    /**
     * @phpstan-param array{max?: numeric|null} $data
     *
     * @phpstan-return numeric|null
     */
    protected function getMaxValue(array $data): int | float | string | null
    {
        return $data['max'] ?? null;
    }
}
