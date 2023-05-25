<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\Core\Repository\Values\Search;

interface SearchContextInterface
{
    /**
     * @param array{}|array{languages: array<string>, useAlwaysAvailable: bool} $languageFilter
     */
    public function setLanguageFilter(array $languageFilter): void;

    /**
     * @return array{}|array{languages: array<string>, useAlwaysAvailable: bool}
     */
    public function getLanguageFilter(): array;

    public function setFilterOnUserPermissions(bool $filterOnUserPermissions): void;

    public function doesFilterOnUserPermissions(): bool;

    /**
     * @return array<string>
     */
    public function getDocumentTypeIdentifiers(): array;
}
