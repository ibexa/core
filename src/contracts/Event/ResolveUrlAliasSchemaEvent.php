<?php
/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\Core\Event;

use Ibexa\Contracts\Core\Repository\Values\Content\Content;
use Ibexa\Contracts\Core\Repository\Values\ContentType\ContentType;
use Symfony\Contracts\EventDispatcher\Event;

final class ResolveUrlAliasSchemaEvent extends Event
{
    /** @var array<string, array> */
    private array $schemaIdentifiers;
    private Content $content;
    private ?ContentType $contentType;

    private array $names = [];
    private string $schemaName;

    public function __construct(
        string $schemaName,
        array $schemaIdentifiers,
        Content $content,
        ContentType $contentType = null
    ) {
        $this->schemaIdentifiers = $schemaIdentifiers;
        $this->content = $content;
        $this->contentType = $contentType;
        $this->schemaName = $schemaName;
    }

    public function getSchemaIdentifiers(): array
    {
        return $this->schemaIdentifiers;
    }

    public function getContent(): Content
    {
        return $this->content;
    }

    public function getContentType(): ?ContentType
    {
        return $this->contentType;
    }

    public function getSchemaName(): string
    {
        return $this->schemaName;
    }

    public function getNames(): array
    {
        return $this->names;
    }

    public function setNames(array $names): void
    {
        $this->names = $names;
    }
}
