<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\Core\Repository\Values\Content\DraftList\Item;

use Ibexa\Contracts\Core\Repository\Values\Content\DraftList\ContentDraftListItemInterface;
use Ibexa\Contracts\Core\Repository\Values\Content\VersionInfo;

/**
 * Item of content drafts list which represents draft to which user has no access for.
 */
class UnauthorizedContentDraftListItem implements ContentDraftListItemInterface
{
    private string $module;

    private string $function;

    /** @var array<string, mixed> */
    private array $payload;

    /**
     * @param array<string, mixed> $payload
     */
    public function __construct(
        string $module,
        string $function,
        array $payload
    ) {
        $this->module = $module;
        $this->function = $function;
        $this->payload = $payload;
    }

    public function getModule(): string
    {
        return $this->module;
    }

    public function getFunction(): string
    {
        return $this->function;
    }

    /**
     * @return array<string, mixed>
     */
    public function getPayload(): array
    {
        return $this->payload;
    }

    public function getVersionInfo(): ?VersionInfo
    {
        return null;
    }

    public function hasVersionInfo(): bool
    {
        return false;
    }
}
