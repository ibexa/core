<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\Core\Event;

use Ibexa\Contracts\Core\Repository\Values\Content\Content;
use Symfony\Contracts\EventDispatcher\Event;

final class ResolveUrlAliasSchemaEvent extends Event
{
    /** @var array<string, array> */
    private array $schemaIdentifiers;

    private Content $content;

    private array $names = [];

    public function __construct(
        array $schemaIdentifiers,
        Content $content
    ) {
        $this->schemaIdentifiers = $schemaIdentifiers;
        $this->content = $content;
    }

    public function getSchemaIdentifiers(): array
    {
        return $this->schemaIdentifiers;
    }

    public function getContent(): Content
    {
        return $this->content;
    }

    public function getTokenValues(): array
    {
        return $this->names;
    }

    public function setTokenValues(array $names): void
    {
        $this->names = $names;
    }
}
