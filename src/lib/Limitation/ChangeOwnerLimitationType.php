<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\Limitation;

use Ibexa\Contracts\Core\Exception\InvalidArgumentType;
use Ibexa\Contracts\Core\Limitation\Type as SPILimitationTypeInterface;
use Ibexa\Contracts\Core\Repository\Exceptions\NotImplementedException;
use Ibexa\Contracts\Core\Repository\Values\Content\ContentCreateStruct;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\CriterionInterface;
use Ibexa\Contracts\Core\Repository\Values\User\Limitation;
use Ibexa\Contracts\Core\Repository\Values\User\Limitation\ChangeOwnerLimitation;
use Ibexa\Contracts\Core\Repository\Values\User\UserReference as APIUserReference;
use Ibexa\Contracts\Core\Repository\Values\ValueObject as APIValueObject;
use Ibexa\Core\FieldType\ValidationError;

final class ChangeOwnerLimitationType extends AbstractPersistenceLimitationType implements SPILimitationTypeInterface
{
    public function acceptValue(Limitation $limitationValue): void
    {
        if (!is_array($limitationValue->limitationValues)) {
            throw new InvalidArgumentType(
                '$limitationValue->limitationValues',
                'array',
                $limitationValue->limitationValues
            );
        }

        foreach ($limitationValue->limitationValues as $key => $value) {
            if (is_string($value) && is_numeric($value)) {
                $limitationValue->limitationValues[$key] = (int)$value;
            }
        }

        $limitationValue->limitationValues = array_filter($limitationValue->limitationValues);
        $limitationValue->limitationValues = array_unique($limitationValue->limitationValues);
    }

    public function validate(Limitation $limitationValue): array
    {
        $validationErrors = [];

        foreach ($limitationValue->limitationValues as $key => $value) {
            if (is_int($value)) {
                continue;
            }

            $validationErrors[] = new ValidationError(
                "limitationValues[%key%] => '%value%' must be an integer",
                null,
                [
                    'value' => $value,
                    'key' => $key,
                ]
            );
        }

        return $validationErrors;
    }

    public function buildValue(array $limitationValues): Limitation
    {
        return new ChangeOwnerLimitation($limitationValues);
    }

    public function evaluate(
        Limitation $value,
        APIUserReference $currentUser,
        APIValueObject $object,
        array $targets = null
    ): ?bool {
        if (!$object instanceof ContentCreateStruct) {
            return self::ACCESS_ABSTAIN;
        }

        $limitationValues = array_filter($value->limitationValues);

        if (empty($limitationValues)) {
            return self::ACCESS_GRANTED;
        }

        $userId = $currentUser->getUserId();
        $limitationValues = array_map(
            static fn (int $value): int => $value === ChangeOwnerLimitation::LIMITATION_VALUE_SELF ? $userId : $value,
            $limitationValues
        );

        if (!is_numeric($object->ownerId)) {
            return self::ACCESS_ABSTAIN;
        }

        if (in_array((int)$object->ownerId, $limitationValues, true)) {
            return self::ACCESS_GRANTED;
        }

        return self::ACCESS_DENIED;
    }

    public function getCriterion(Limitation $value, APIUserReference $currentUser): CriterionInterface
    {
        return new Criterion\UserMetadata(
            Criterion\UserMetadata::OWNER,
            Criterion\Operator::IN,
            $value->limitationValues
        );
    }

    /**
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotImplementedException
     */
    public function valueSchema(): array|int
    {
        throw new NotImplementedException(__METHOD__);
    }
}
