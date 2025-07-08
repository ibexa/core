<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\Base\Exceptions;

use Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException as APINotFoundException;
use Ibexa\Core\Base\Translatable;
use Ibexa\Core\Base\TranslatableBase;
use Throwable;

/**
 * Not Found Exception implementation.
 *
 * Usage:
 * ```
 * throw new NotFoundException('Content', 42);
 * ```
 */
class NotFoundException extends APINotFoundException implements Httpable, Translatable
{
    use TranslatableBase;

    /**
     * Generates: Could not find '{$what}' with identifier '{$identifier}'.
     */
    public function __construct(string $what, mixed $identifier, ?Throwable $previous = null)
    {
        $identifierStr = is_string($identifier) ? $identifier : var_export($identifier, true);
        $this->setMessageTemplate("Could not find '%what%' with identifier '%identifier%'");
        $this->setParameters(['%what%' => $what, '%identifier%' => $identifierStr]);
        parent::__construct($this->getBaseTranslation(), self::NOT_FOUND, $previous);
    }
}
