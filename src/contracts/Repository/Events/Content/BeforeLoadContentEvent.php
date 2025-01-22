<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\Core\Repository\Events\Content;

use Ibexa\Contracts\Core\Repository\Event\BeforeEvent;
use Ibexa\Contracts\Core\Repository\Values\Content\Content;
use UnexpectedValueException;

final class BeforeLoadContentEvent extends BeforeEvent
{
    private int $contentId;

    /** @var string[]|null */
    private ?array $languages;

    private ?int $versionNo;

    private bool $useAlwaysAvailable;

    private ?Content $content = null;

    /**
     * @param string[] $languages
     */
    public function __construct(
        int $contentId,
        ?array $languages = null,
        ?int $versionNo = null,
        bool $useAlwaysAvailable = true
    ) {
        $this->contentId = $contentId;
        $this->languages = $languages;
        $this->versionNo = $versionNo;
        $this->useAlwaysAvailable = $useAlwaysAvailable;
    }

    public function getContentId(): int
    {
        return $this->contentId;
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

    public function getContent(): Content
    {
        if (!$this->hasContent()) {
            throw new UnexpectedValueException(
                sprintf(
                    'Return value is not set or not of type %s. Check hasContent() or set it using setContent() before you call the getter.',
                    Content::class
                )
            );
        }

        return $this->content;
    }

    public function setContent(?Content $content): void
    {
        $this->content = $content;
    }

    /** @phpstan-assert-if-true !null $this->content */
    public function hasContent(): bool
    {
        return $this->content instanceof Content;
    }
}
