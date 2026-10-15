<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\Base;

/**
 * Trait providing a default implementation of Translatable.
 */
trait TranslatableBase
{
    private string $messageTemplate;

    /** @var array<string, mixed> */
    private array $parameters = [];

    public function setMessageTemplate(string $messageTemplate): void
    {
        $this->messageTemplate = $messageTemplate;
    }

    public function getMessageTemplate(): string
    {
        return $this->messageTemplate;
    }

    public function setParameters(array $parameters): void
    {
        $this->parameters = $parameters;
    }

    public function addParameter(
        string $name,
        string $value
    ): void {
        $this->parameters[$name] = $value;
    }

    public function addParameters(array $parameters): void
    {
        $this->parameters += $parameters;
    }

    public function getParameters(): array
    {
        return $this->parameters;
    }

    public function getBaseTranslation(): string
    {
        return strtr($this->messageTemplate, $this->parameters);
    }
}
