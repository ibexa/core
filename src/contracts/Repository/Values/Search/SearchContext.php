<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\Core\Repository\Values\Search;

final class SearchContext implements SearchContextInterface
{
    /** @var array{}|array{languages: array<string>, useAlwaysAvailable: bool} */
    private array $languageFilter;

    private bool $filterOnUserPermissions;

    /** @var array<string> */
    private array $documentTypeIdentifiers;

    /**
     * @param array{}|array{languages: array<string>, useAlwaysAvailable: bool} $languageFilter
     * @param array<string> $documentTypeIdentifiers
     */
    public function __construct(
        array $languageFilter = [],
        bool $filterOnUserPermissions = true,
        array $documentTypeIdentifiers = []
    ) {
        $this->languageFilter = $languageFilter;
        $this->filterOnUserPermissions = $filterOnUserPermissions;
        $this->documentTypeIdentifiers = $documentTypeIdentifiers;
    }

    public function setLanguageFilter(array $languageFilter): void
    {
        $this->languageFilter = $languageFilter;
    }

    public function getLanguageFilter(): array
    {
        return $this->languageFilter;
    }

    public function setFilterOnUserPermissions(bool $filterOnUserPermissions): void
    {
        $this->filterOnUserPermissions = $filterOnUserPermissions;
    }

    public function doesFilterOnUserPermissions(): bool
    {
        return $this->filterOnUserPermissions;
    }

    /**
     * @return array<string>
     */
    public function getDocumentTypeIdentifiers(): array
    {
        return $this->documentTypeIdentifiers;
    }
}
