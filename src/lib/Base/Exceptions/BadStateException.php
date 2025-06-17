<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\Base\Exceptions;

use Exception;
use Ibexa\Contracts\Core\Repository\Exceptions\BadStateException as APIBadStateException;
use Ibexa\Core\Base\Translatable;
use Ibexa\Core\Base\TranslatableBase;

/**
 * BadState Exception implementation.
 *
 * Usage:
 * ```
 * throw new BadStateException('nodes', 'array');
 * ```
 */
class BadStateException extends APIBadStateException implements Translatable
{
    use TranslatableBase;

    /**
     * Generates: "Argument '{$argumentName}' has a bad state: {$whatIsWrong}".
     */
    public function __construct(string $argumentName, string $whatIsWrong, ?Exception $previous = null)
    {
        $this->setMessageTemplate("Argument '%argumentName%' has a bad state: %whatIsWrong%");
        $this->setParameters(['%argumentName%' => $argumentName, '%whatIsWrong%' => $whatIsWrong]);
        parent::__construct($this->getBaseTranslation(), 0, $previous);
    }
}
