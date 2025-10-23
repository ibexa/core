<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\Core\Repository\Validator;

use Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException;
use Ibexa\Contracts\Core\Repository\Values\ValueObject;

interface ContentValidator
{
    public function supports(ValueObject $object): bool;

    /**
     * Validates given content related ValueObject returning field errors structure as a result.
     *
     * @param array $context Additional context parameters to be used by validators.
     * @param string[]|null $fieldIdentifiers List of field identifiers for partial validation or null for
     *                      case of full validation. Empty identifiers array is equal to no validation.
     *
     * @phpstan-return array<
     *     int,
     *     array<
     *         string,
     *         \Ibexa\Contracts\Core\FieldType\ValidationError|\Ibexa\Contracts\Core\FieldType\ValidationError[]
     *     >
     * >
     *
     * @return array Grouped validation errors by field definition ID and language code, in format:
     *           $returnValue[int $fieldDefinitionId][string $languageCode] = $fieldErrors;
     *
     * @throws InvalidArgumentException
     */
    public function validate(
        ValueObject $object,
        array $context = [],
        ?array $fieldIdentifiers = null
    ): array;
}
