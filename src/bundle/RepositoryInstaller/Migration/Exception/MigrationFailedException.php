<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Bundle\RepositoryInstaller\Migration\Exception;

use Ibexa\Bundle\RepositoryInstaller\Migration\TaggedMigrationsRunner;
use RuntimeException;
use Throwable;

/**
 * Thrown by {@see TaggedMigrationsRunner} when one of the
 * migrations it runs fails. Names that migration, since Doctrine's executor rethrows the original
 * error as it was, and carries that error as the previous exception.
 */
final class MigrationFailedException extends RuntimeException
{
    private string $version;

    public function __construct(
        string $version,
        Throwable $previous
    ) {
        parent::__construct(sprintf('Migration "%s" failed: %s', $version, $previous->getMessage()), 0, $previous);

        $this->version = $version;
    }

    public function getVersion(): string
    {
        return $this->version;
    }
}
