<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\Repository\Strategy\ContentPublication;

use Ibexa\Contracts\Core\Repository\Strategy\ContentPublication\ContentPublicationStrategyInterface;
use Ibexa\Contracts\Core\Repository\Values\Content\Language;
use Ibexa\Contracts\Core\Repository\Values\Content\VersionInfo;
use LogicException;

/**
 * Dispatches publication to the first supporting strategy, in priority order.
 * Exactly one strategy executes per publication.
 *
 * @internal Meant for internal use by Repository
 */
final readonly class ChainContentPublicationStrategy implements ContentPublicationStrategyInterface
{
    /**
     * @param iterable<ContentPublicationStrategyInterface> $strategies
     */
    public function __construct(
        private iterable $strategies,
    ) {
    }

    public function supports(): bool
    {
        foreach ($this->strategies as $strategy) {
            if ($strategy->supports()) {
                return true;
            }
        }

        return false;
    }

    public function publishVersion(VersionInfo $versionInfo, array $translations = Language::ALL): void
    {
        foreach ($this->strategies as $strategy) {
            if ($strategy->supports()) {
                $strategy->publishVersion($versionInfo, $translations);

                return;
            }
        }

        throw new LogicException(sprintf(
            'No content publication strategy supports the current publication. At least %s'
            . ' must be tagged with "ibexa.repository.content.publication_strategy".',
            SynchronousContentPublicationStrategy::class,
        ));
    }
}
