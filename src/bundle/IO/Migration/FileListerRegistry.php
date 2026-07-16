<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Bundle\IO\Migration;

use Ibexa\Core\Base\Exceptions\NotFoundException;

/**
 * A registry of FileListerInterfaces.
 */
interface FileListerRegistry
{
    /**
     * Returns the FileListerInterface matching the argument.
     *
     * @param string $identifier An identifier string.
     *
     * @return FileListerInterface The FileListerInterface given by the identifier.
     *
     * @throws NotFoundException If no FileListerInterface exists with this identifier
     */
    public function getItem(string $identifier): FileListerInterface;

    /**
     * Returns the identifiers of all registered FileListerInterfaces.
     *
     * @return string[] Array of identifier strings.
     */
    public function getIdentifiers(): array;
}
