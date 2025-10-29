<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\Repository\Validator\Constraint;

use Ibexa\Contracts\Core\Repository\LocationService;
use Ibexa\Contracts\Core\Repository\Values\Content\LocationCreateStruct;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

final class LocationIsContainerContentTypeValidator extends ConstraintValidator
{
    public function __construct(
        private LocationService $locationService
    ) {}

    /**
     * @param LocationCreateStruct $value
     */
    public function validate(
        $value,
        Constraint $constraint
    ): void {
        if (!$constraint instanceof LocationIsContainerContentType) {
            throw new UnexpectedTypeException($constraint, LocationIsContainerContentType::class);
        }

        $parentLocation = $this->locationService->loadLocation($value->parentLocationId);

        if ($parentLocation->getDepth() === 0) {
            // Parent location is root location, which is always a container.
            return;
        }

        if (!$parentLocation->getContentInfo()->getContentType()->isContainer()) {
            $this->context->buildViolation($constraint->message)
                ->setParameter('{{ contentTypeName }}', (string) $parentLocation->getContentInfo()->getContentType()->getName())
                ->setCode(LocationIsContainerContentType::LOCATION_IS_NOT_CONTAINER_ERROR)
                ->addViolation();
        }
    }
}
