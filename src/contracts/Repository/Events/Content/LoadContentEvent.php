<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\Core\Repository\Events\Content;

use Ibexa\Contracts\Core\Repository\Event\AfterEvent;
use Ibexa\Contracts\Core\Repository\Values\Content\Content;

final class LoadContentEvent extends AfterEvent
{
    private int $contentId;

    private Content $content;

    /** @var string[]|null */
    private ?array $languages;

    private ?int $versionNo;

    private bool $useAlwaysAvailable;

    /**
     * @param string[] $languages
     */
    public function __construct(
        Content $content,
        int $contentId,
        ?array $languages = null,
        ?int $versionNo = null,
        bool $useAlwaysAvailable = true
    ) {
        $this->contentId = $contentId;
        $this->content = $content;
        $this->languages = $languages;
        $this->versionNo = $versionNo;
        $this->useAlwaysAvailable = $useAlwaysAvailable;
    }

    public function getContent(): Content
    {
        return $this->content;
    }

    /**
     * @return string[]|null
     */
    public function getLanguages(): ?array
    {
        return $this->languages;
    }

    public function getVersionNo(): ?int
    {
        return $this->versionNo;
    }

    public function getUseAlwaysAvailable(): bool
    {
        return $this->useAlwaysAvailable;
    }

    public function getContentId(): int
    {
        return $this->contentId;
    }
}
