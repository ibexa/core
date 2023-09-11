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
    /** @var array<string, array<int, string>> */
    private array $schemaIdentifiers;

    /** @var array<string, array<string, string>> */
    private array $tokenValues = [];

    /**
     * @param array<string, array<int, string>> $schemaIdentifiers
     */
    public function __construct(array $schemaIdentifiers)
    {
        $this->schemaIdentifiers = $schemaIdentifiers;
    }

    /**
     * @return array<string, array<string, string>>
     */
    final public function getTokenValues(): array
    {
        return $this->tokenValues;
    }

    /**
     * @param array<string, array<string, string>> $names
     */
    final public function setTokenValues(array $names): void
    {
        $this->tokenValues = $names;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function getSchemaIdentifiers(): array
    {
        return $this->schemaIdentifiers;
    }
}
