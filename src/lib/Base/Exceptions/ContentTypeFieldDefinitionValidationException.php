<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\Base\Exceptions;

use Ibexa\Contracts\Core\FieldType\ValidationError;
use Ibexa\Contracts\Core\Repository\Exceptions\ContentTypeFieldDefinitionValidationException as APIContentTypeFieldDefinitionValidationException;
use Ibexa\Core\Base\Translatable;
use Ibexa\Core\Base\TranslatableBase;

/**
 * This Exception is thrown on create or update content one or more given fields are not valid.
 */
class ContentTypeFieldDefinitionValidationException extends APIContentTypeFieldDefinitionValidationException implements Translatable
{
    use TranslatableBase;

    /** @var array<string, ValidationError[]> */
    protected array $errors;

    /**
     * Generates: Content type field definitions did not validate.
     *
     * @param array<string, ValidationError[]> $errors
     */
    public function __construct(array $errors)
    {
        $this->errors = $errors;
        $this->setMessageTemplate('Content type field definitions did not validate');
        parent::__construct($this->getBaseTranslation());
    }

    /**
     * @return array<string, ValidationError[]>
     */
    public function getFieldErrors(): array
    {
        return $this->errors;
    }
}
