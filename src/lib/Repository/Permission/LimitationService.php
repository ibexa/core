<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\Repository\Permission;

use Ibexa\Contracts\Core\FieldType\ValidationError;
use Ibexa\Contracts\Core\Limitation\Type;
use Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException;
use Ibexa\Contracts\Core\Repository\Values\User\Limitation;
use Ibexa\Core\Base\Exceptions\BadStateException;
use Ibexa\Core\Base\Exceptions\NotFound\LimitationNotFoundException;
use Traversable;

/**
 * Internal service to deal with limitations and limitation types.
 *
 * @internal Meant for internal use by Repository.
 */
class LimitationService
{
    /** @var Type[] */
    private $limitationTypes;

    public function __construct(?Traversable $limitationTypes = null)
    {
        $this->limitationTypes = null !== $limitationTypes
            ? iterator_to_array($limitationTypes) :
            [];
    }

    /**
     * Returns the LimitationType registered with the given identifier.
     *
     * Returns the correct implementation of API Limitation value object
     * based on provided identifier
     *
     * @throws LimitationNotFoundException
     */
    public function getLimitationType(string $identifier): Type
    {
        if (!isset($this->limitationTypes[$identifier])) {
            throw new LimitationNotFoundException($identifier);
        }

        return $this->limitationTypes[$identifier];
    }

    /**
     * Validates an array of Limitations.
     *
     * @param Limitation[] $limitations
     *
     * @return ValidationError[]
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\BadStateException
     * @throws InvalidArgumentException
     */
    public function validateLimitations(array $limitations): array
    {
        $allErrors = [];
        foreach ($limitations as $limitation) {
            $errors = $this->validateLimitation($limitation);
            if (!empty($errors)) {
                $allErrors[$limitation->getIdentifier()] = $errors;
            }
        }

        return $allErrors;
    }

    /**
     * Validates single Limitation.
     *
     * @return ValidationError[]
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\BadStateException If the Role settings is in a bad state*@throws InvalidArgumentException
     * @throws InvalidArgumentException
     */
    public function validateLimitation(Limitation $limitation): array
    {
        $identifier = $limitation->getIdentifier();
        if (!isset($this->limitationTypes[$identifier])) {
            throw new BadStateException(
                '$identifier',
                "limitationType[{$identifier}] is not configured"
            );
        }

        $type = $this->limitationTypes[$identifier];

        // This will throw if it does not pass
        $type->acceptValue($limitation);

        // This return array of validation errors
        return $type->validate($limitation);
    }
}
