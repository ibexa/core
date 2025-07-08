<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Bundle\IO\Migration;

interface MigrationHandlerInterface
{
    /**
     * Set the from/to handlers based on identifiers.
     */
    public function setIODataHandlersByIdentifiers(
        string $fromMetadataHandlerIdentifier,
        string $fromBinarydataHandlerIdentifier,
        string $toMetadataHandlerIdentifier,
        string $toBinarydataHandlerIdentifier
    ): MigrationHandler;
}
