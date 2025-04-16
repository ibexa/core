<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\MVC\Symfony\SiteAccess\Matcher;

use Ibexa\Core\MVC\Symfony\SiteAccess\VersatileMatcher;

/**
 * @internal
 *
 * @phpstan-type TSiteAccessConfigurationArray array{prefix?: string, suffix?: string}
 */
abstract class AffixBasedTextMatcher extends Regex implements VersatileMatcher
{
    protected readonly string $prefix;

    protected readonly string $suffix;

    abstract protected function buildRegex(): string;

    abstract protected function getMatchedItemNumber(): int;

    /**
     * @phpstan-param TSiteAccessConfigurationArray $siteAccessesConfiguration
     */
    public function __construct(private readonly array $siteAccessesConfiguration)
    {
        $this->prefix = $this->siteAccessesConfiguration['prefix'] ?? '';
        $this->suffix = $this->siteAccessesConfiguration['suffix'] ?? '';

        parent::__construct($this->buildRegex(), $this->getMatchedItemNumber());
    }

    /**
     * @phpstan-return TSiteAccessConfigurationArray
     */
    public function getSiteAccessesConfiguration(): array
    {
        return $this->siteAccessesConfiguration;
    }
}
