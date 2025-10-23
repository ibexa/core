<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\Core\Validation;

use Symfony\Component\Validator\Validator\ValidatorInterface;

final class StructValidator
{
    private ValidatorInterface $validator;

    public function __construct(ValidatorInterface $validator)
    {
        $this->validator = $validator;
    }

    /**
     * @throws ValidationFailedException
     *
     * @param string[] $groups
     */
    public function assertValidStruct(
        string $name,
        object $struct,
        array $groups
    ): void {
        $errors = $this->validator->validate($struct, null, ['Default', ...$groups]);
        if ($errors->count() > 0) {
            throw new ValidationFailedException($name, $errors);
        }
    }
}
