<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\FieldType;

use Ibexa\Contracts\Core\FieldType\ValidationError as ValidationErrorInterface;
use Ibexa\Contracts\Core\Repository\Values\Translation;
use Ibexa\Contracts\Core\Repository\Values\Translation\Message;
use Ibexa\Contracts\Core\Repository\Values\Translation\Plural;

/**
 * Class for validation errors.
 */
class ValidationError implements ValidationErrorInterface
{
    protected string $singular;

    protected ?string $plural;

    /** @phpstan-var array<string, scalar> */
    protected array $values;

    /**
     * Element on which the error occurred
     * e.g. property name or property path compatible with a Symfony PropertyAccess component.
     *
     * Example: StringLengthValidator[minStringLength]
     */
    protected ?string $target;

    /**
     * @phpstan-param array<string, scalar> $values
     */
    public function __construct(string $singular, ?string $plural = null, array $values = [], ?string $target = null)
    {
        $this->singular = $singular;
        $this->plural = $plural;
        $this->values = $values;
        $this->target = $target;
    }

    public function getTranslatableMessage(): Translation
    {
        if (null !== $this->plural) {
            return new Plural(
                $this->singular,
                $this->plural,
                $this->values
            );
        }

        return new Message(
            $this->singular,
            $this->values
        );
    }

    public function setTarget(string $target): void
    {
        $this->target = $target;
    }

    public function getTarget(): ?string
    {
        return $this->target;
    }
}
