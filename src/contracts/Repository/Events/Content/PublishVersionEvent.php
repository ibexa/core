<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\Core\Repository\Events\Content;

use Ibexa\Contracts\Core\Repository\Event\AfterEvent;
use Ibexa\Contracts\Core\Repository\Values\Content\Content;
use Ibexa\Contracts\Core\Repository\Values\Content\VersionInfo;

final class PublishVersionEvent extends AfterEvent
{
    private Content $content;

    private VersionInfo $versionInfo;

    /** @var string[] */
    private array $translations;

    public function __construct(
        Content $content,
        VersionInfo $versionInfo,
        array $translations
    ) {
        $this->content = $content;
        $this->versionInfo = $versionInfo;
        $this->translations = $translations;
    }

    public function getContent(): Content
    {
        return $this->content;
    }

    public function getVersionInfo(): VersionInfo
    {
        return $this->versionInfo;
    }

    /**
     * @return string[]
     */
    public function getTranslations(): array
    {
        return $this->translations;
    }
}
