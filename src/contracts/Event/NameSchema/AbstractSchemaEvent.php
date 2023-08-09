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
    protected array $schemaIdentifiers;

    /** @var array<string, array<string>> */
    protected array $tokenValues = [];

    public function __construct(array $schemaIdentifiers)
    {
        $this->schemaIdentifiers = $schemaIdentifiers;
    }

    public function getTokenValues(): array
    {
        return $this->tokenValues;
    }

    public function setTokenValues(array $names): void
    {
        $this->tokenValues = $names;
    }

    public function getSchemaIdentifiers(): array
    {
        return $this->schemaIdentifiers;
    }
}
