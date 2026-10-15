<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Core\Limitation;

use Ibexa\Contracts\Core\Limitation\Type as SPILimitationTypeInterface;
use Ibexa\Contracts\Core\Repository\Exceptions\NotImplementedException;
use Ibexa\Contracts\Core\Repository\Values\Content\Content;
use Ibexa\Contracts\Core\Repository\Values\Content\ContentCreateStruct;
use Ibexa\Contracts\Core\Repository\Values\Content\ContentInfo;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\CriterionInterface;
use Ibexa\Contracts\Core\Repository\Values\Content\VersionInfo;
use Ibexa\Contracts\Core\Repository\Values\User\Limitation;
use Ibexa\Contracts\Core\Repository\Values\User\Limitation as APILimitationValue;
use Ibexa\Contracts\Core\Repository\Values\User\Limitation\OwnerLimitation as APIOwnerLimitation;
use Ibexa\Contracts\Core\Repository\Values\User\UserReference;
use Ibexa\Contracts\Core\Repository\Values\User\UserReference as APIUserReference;
use Ibexa\Core\Base\Exceptions\BadStateException;
use Ibexa\Core\Base\Exceptions\InvalidArgumentException;
use Ibexa\Core\Base\Exceptions\InvalidArgumentType;
use Ibexa\Core\FieldType\ValidationError;

/**
 * OwnerLimitation is a Content limitation.
 */
class OwnerLimitationType extends AbstractPersistenceLimitationType implements SPILimitationTypeInterface
{
    /**
     * Accepts a Limitation value and checks for structural validity.
     *
     * Makes sure LimitationValue object and ->limitationValues is of correct type.
     *
     * @param Limitation $limitationValue
     *
     *@throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException If the value does not match the expected type/structure
     */
    public function acceptValue(APILimitationValue $limitationValue): void
    {
        if (!$limitationValue instanceof APIOwnerLimitation) {
            throw new InvalidArgumentType('$limitationValue', 'APIOwnerLimitation', $limitationValue);
        } elseif (!is_array($limitationValue->limitationValues)) {
            throw new InvalidArgumentType('$limitationValue->limitationValues', 'array', $limitationValue->limitationValues);
        }

        foreach ($limitationValue->limitationValues as $key => $value) {
            // Cast integers passed as string to int
            if (is_string($value) && ctype_digit($value)) {
                $limitationValue->limitationValues[$key] = (int)$value;
            } elseif (!is_int($value)) {
                throw new InvalidArgumentType("\$limitationValue->limitationValues[{$key}]", 'int', $value);
            }
        }
    }

    /**
     * Makes sure LimitationValue->limitationValues is valid according to valueSchema().
     *
     * Make sure {@link acceptValue()} is checked first!
     *
     * @param Limitation $limitationValue
     *
     * @return \Ibexa\Contracts\Core\FieldType\ValidationError[]
     */
    public function validate(APILimitationValue $limitationValue): array
    {
        $validationErrors = [];
        foreach ($limitationValue->limitationValues as $key => $value) {
            if ($value !== 1 && $value !== 2) {
                $validationErrors[] = new ValidationError(
                    "limitationValues[%key%] => '%value%' must be either 1 (owner) or 2 (session)",
                    null,
                    [
                        'value' => $value,
                        'key' => $key,
                    ]
                );
            }
        }

        return $validationErrors;
    }

    /**
     * Create the Limitation Value.
     *
     * @param mixed[] $limitationValues
     *
     * @return Limitation
     */
    public function buildValue(array $limitationValues): APILimitationValue
    {
        return new APIOwnerLimitation(['limitationValues' => $limitationValues]);
    }

    /**
     * Evaluate permission against content & target(placement/parent/assignment).
     *
     * @param Limitation $value
     * @param UserReference $currentUser
     * @param object $object
     * @param object[]|null $targets The context of the $object, like Location of Content, if null none where provided by caller
     *
     * @return bool|null
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\BadStateException If value of the LimitationValue is unsupported
     *         Example if OwnerLimitationValue->limitationValues[0] is not one of: [ 1,  2 ]
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException If any of the arguments are invalid
     *         Example: If LimitationValue is instance of ContentTypeLimitationValue, and Type is SectionLimitationType.
     *
     * @todo Add support for $limitationValues[0] == 2 when session values can be injected somehow, or deprecate
     */
    public function evaluate(
        APILimitationValue $value,
        APIUserReference $currentUser,
        object $object,
        ?array $targets = null
    ): ?bool {
        if (!$value instanceof APIOwnerLimitation) {
            throw new InvalidArgumentException('$value', 'Must be of type: APIOwnerLimitation');
        }

        if ($value->limitationValues[0] != 1 && $value->limitationValues[0] != 2) {
            throw new BadStateException(
                'Owner limitation',
                'Expected Limitation value to be 1 or 2 instead of' . $value->limitationValues[0]
            );
        }

        if ($object instanceof Content) {
            $object = $object->getVersionInfo()->getContentInfo();
        } elseif ($object instanceof VersionInfo) {
            $object = $object->getContentInfo();
        } elseif (!$object instanceof ContentInfo && !$object instanceof ContentCreateStruct) {
            throw new InvalidArgumentException(
                '$object',
                'Must be of type: ContentCreateStruct, Content, VersionInfo or ContentInfo'
            );
        }

        $userId = $currentUser->getUserId();

        /*
         * @var $object ContentInfo
         */
        $isOwner = $object->ownerId === $userId;
        $isSelf = $object instanceof ContentInfo && $object->id === $userId;

        return $isOwner || $isSelf;
    }

    /**
     * Returns Criterion for use in find() query.
     *
     * @param Limitation $value
     * @param UserReference $currentUser
     *
     * @return CriterionInterface
     *
     * @todo Add support for $limitationValues[0] == 2 when session values can be injected somehow, or deprecate
     */
    public function getCriterion(
        APILimitationValue $value,
        APIUserReference $currentUser
    ): CriterionInterface {
        if (empty($value->limitationValues)) {
            // A Policy should not have empty limitationValues stored
            throw new \RuntimeException('$value->limitationValues is empty');
        }

        if ($value->limitationValues[0] != 1 && $value->limitationValues[0] != 2) {
            throw new BadStateException(
                'Parent User Group limitation',
                'Expected Limitation value to be 1 or 2 instead of' . $value->limitationValues[0]
            );
        }

        return new Criterion\UserMetadata(
            Criterion\UserMetadata::OWNER,
            Criterion\Operator::EQ,
            $currentUser->getUserId()
        );
    }

    /**
     * Returns info on valid $limitationValues.
     *
     * @return int|mixed[] In case of array, a hash with key as valid limitations value and value as human readable name
     *                     of that option, in case of int on of VALUE_SCHEMA_ constants.
     */
    public function valueSchema(): array | int
    {
        throw new NotImplementedException(__METHOD__);
    }
}
