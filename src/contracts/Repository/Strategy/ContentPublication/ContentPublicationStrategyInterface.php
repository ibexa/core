<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\Core\Repository\Strategy\ContentPublication;

use Ibexa\Contracts\Core\Repository\Values\Content\Language;
use Ibexa\Contracts\Core\Repository\Values\Content\VersionInfo;

/**
 * Entry point for publishing a content version, hiding whether the publication happens
 * synchronously (in-request) or asynchronously (queued background work).
 *
 * Strategies are registered with the "ibexa.repository.content.publication_strategy" service tag
 * and consulted in priority order; the first one supporting the current repository executes.
 */
interface ContentPublicationStrategyInterface
{
    public function supports(): bool;

    /**
     * @param list<string> $translations List of language codes of translations which will be
     * included in a published version
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\BadStateException
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\UnauthorizedException
     */
    public function publishVersion(VersionInfo $versionInfo, array $translations = Language::ALL): void;
}
