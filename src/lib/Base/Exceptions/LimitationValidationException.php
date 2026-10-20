<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\Base\Exceptions;

use Ibexa\Contracts\Core\Repository\Exceptions\LimitationValidationException as APILimitationValidationException;
use Ibexa\Core\Base\Translatable;
use Ibexa\Core\Base\TranslatableBase;
use Ibexa\Core\FieldType\ValidationError;

/**
 * This Exception is thrown on create, update or assign policy or role
 * when one or more given limitations are not valid.
 */
class LimitationValidationException extends APILimitationValidationException implements Translatable
{
    use TranslatableBase;

    /**
     * Contains an array of limitation ValidationError objects.
     *
     * @var ValidationError[]
     */
    protected array $errors;

    /**
     * Generates: Limitations did not validate.
     *
     * Also sets the given $errors to the internal property, retrievable by getValidationErrors()
     *
     * @param ValidationError[] $errors
     */
    public function __construct(array $errors)
    {
        $this->errors = $errors;
        $this->setMessageTemplate('Limitations did not validate');
        parent::__construct($this->getBaseTranslation());
    }

    /**
     * Returns an array of limitation ValidationError objects.
     *
     * @return ValidationError[]
     */
    public function getLimitationErrors(): array
    {
        return $this->errors;
    }
}
