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
 * Usage: throw new InvalidArgument('nodes', 'array');
 */
class InvalidArgumentValue extends InvalidArgumentException
{
    /**
     * Generates: "Argument '{$argumentName}' is invalid: '{$value}' is wrong value[ in class '{$className}']".
     *
     * @param string|null $className Optionally to specify class in abstract/parent classes
     */
    public function __construct(string $argumentName, mixed $value, ?string $className = null, ?Exception $previous = null)
    {
        $valueStr = is_string($value) ? $value : var_export($value, true);
        $parameters = ['%actualValue%' => $valueStr];
        if (empty($className)) {
            $this->setMessageTemplate("'%actualValue%' is incorrect value");
        } else {
            $this->setMessageTemplate("'%actualValue%' is incorrect value in class '%className%'");
            $parameters['%className%'] = $className;
        }
        $whatIsWrong = $this->getMessageTemplate();

        parent::__construct($argumentName, $whatIsWrong, $previous);

        // Alter the message template & inject new parameters.
        $this->setMessageTemplate(/** @Ignore */str_replace('%whatIsWrong%', $whatIsWrong, $this->getMessageTemplate()));
        $this->addParameters($parameters);
        $this->message = $this->getBaseTranslation();
    }
}
