<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Bundle\IO\Migration\FileListerRegistry;

use Ibexa\Bundle\IO\Migration\FileListerInterface;
use Ibexa\Bundle\IO\Migration\FileListerRegistry;
use Ibexa\Core\Base\Exceptions\NotFoundException;

/**
 * A registry of FileListerInterfaces which is configurable via the array passed to its constructor.
 */
final class ConfigurableRegistry implements FileListerRegistry
{
    /**
     * @param FileListerInterface[] $registry Hash of FileListerInterfaces, with identifier string as key.
     */
    public function __construct(private readonly array $registry = []) {}

    /**
     * Returns the FileListerInterface matching the argument.
     *
     * @param string $identifier An identifier string.
     *
     * @return FileListerInterface The FileListerInterface given by the identifier.
     *
     *@throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException If no FileListerInterface exists with this identifier
     */
    public function getItem(string $identifier): FileListerInterface
    {
        if (isset($this->registry[$identifier])) {
            return $this->registry[$identifier];
        }

        throw new NotFoundException('Migration file lister', $identifier);
    }

    /**
     * Returns the identifiers of all registered FileListerInterfaces.
     *
     * @return string[] Array of identifier strings.
     */
    public function getIdentifiers(): array
    {
        return array_keys($this->registry);
    }
}
