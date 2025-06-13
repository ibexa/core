<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\Core\FieldType\ValidationError;

use Ibexa\Contracts\Core\FieldType\ValidationError;
use Ibexa\Contracts\Core\Repository\Values\Translation;
use Ibexa\Contracts\Core\Repository\Values\Translation\Message;

/**
 * @internal
 */
abstract class AbstractValidationError implements ValidationError
{
    protected string $message;

    /** @var array<string, scalar> */
    protected array $parameters;

    /**
     * Element on which the error occurred
     * e.g., property name or property path compatible with Symfony PropertyAccess component.
     *
     * Example: StringLengthValidator[minStringLength]
     *
     * @var string
     */
    protected string $target;

    /**
     * @param array<string, scalar> $parameters
     */
    public function __construct(string $message, array $parameters, string $target)
    {
        $this->message = $message;
        $this->parameters = $parameters;
        $this->target = $target;
    }

    public function getTranslatableMessage(): Translation
    {
        return new Message($this->message, $this->parameters);
    }

    public function setTarget(string $target): void
    {
        $this->target = $target;
    }

    public function getTarget(): string
    {
        return $this->target;
    }
}
