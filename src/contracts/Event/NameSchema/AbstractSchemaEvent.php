<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\Core\Event\NameSchema;

use Symfony\Contracts\EventDispatcher\Event;

abstract class AbstractSchemaEvent extends Event
{
    /** @var array<string, array> */
    private array $schemaIdentifiers;

    /** @var array<string, array<string>> */
    private array $tokenValues = [];

    public function __construct(array $schemaIdentifiers)
    {
        $this->schemaIdentifiers = $schemaIdentifiers;
    }

    final public function getTokenValues(): array
    {
        return $this->tokenValues;
    }

    final public function setTokenValues(array $names): void
    {
        $this->tokenValues = $names;
    }

    public function getSchemaIdentifiers(): array
    {
        return $this->schemaIdentifiers;
    }
}
