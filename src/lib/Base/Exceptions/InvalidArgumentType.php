<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\Base\Exceptions;

use Exception;
use JMS\TranslationBundle\Annotation\Ignore;

/**
 * Invalid Argument Type Exception implementation.
 *
 * Usage:
 * ```
 * throw new InvalidArgument('nodes', 'array');
 * ```
 */
class InvalidArgumentType extends InvalidArgumentException
{
    /**
     * Generates: "Argument '{$argumentName}' is invalid: expected value to be of type '{$expectedType}'[, got '{$value}']".
     *
     * @param mixed|null $value Optionally to output the type that was received
     */
    public function __construct(
        string $argumentName,
        string $expectedType,
        mixed $value = null,
        ?Exception $previous = null
    ) {
        parent::__construct($argumentName, "Argument $argumentName is invalid", $previous);
        // override parent constructor message template and parameters
        $parameters = ['%argumentName%' => $argumentName, '%expectedType%' => $expectedType];
        $this->setMessageTemplate("Argument '%argumentName%' is invalid: value must be of type '%expectedType%'");
        if (null !== $value) {
            $this->setMessageTemplate("Argument '%argumentName%' is invalid: value must be of type '%expectedType%', not '%actualType%'");
            $actualType = get_debug_type($value);
            $parameters['%actualType%'] = $actualType;
        }

        $this->addParameters($parameters);
        $this->message = /** @Ignore */$this->getBaseTranslation();
    }
}
