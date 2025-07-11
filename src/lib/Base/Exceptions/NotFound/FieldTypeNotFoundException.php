<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\Base\Exceptions\NotFound;

use Exception;
use Ibexa\Core\Base\Exceptions\Httpable;
use Ibexa\Core\Base\Translatable;
use Ibexa\Core\Base\TranslatableBase;
use Ibexa\Core\FieldType\Null\Type as NullType;
use RuntimeException;

/**
 * FieldType Not Found Exception.
 */
class FieldTypeNotFoundException extends RuntimeException implements Httpable, Translatable
{
    use TranslatableBase;

    /**
     * Creates a FieldType Not Found exception with info on how to fix.
     */
    public function __construct(string $fieldType, ?Exception $previous = null)
    {
        $this->setMessageTemplate(
            "Field Type '%fieldType%' not found. It must be implemented or configured to use %nullType%"
        );
        $this->setParameters(
            [
                '%fieldType%' => $fieldType,
                '%nullType%' => NullType::class,
            ]
        );

        parent::__construct($this->getBaseTranslation(), self::INTERNAL_ERROR, $previous);
    }
}
