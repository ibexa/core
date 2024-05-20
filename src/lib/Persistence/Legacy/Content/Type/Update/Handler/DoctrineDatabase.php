<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\Persistence\Legacy\Content\Type\Update\Handler;

use Ibexa\Contracts\Core\Persistence\Content\Type;
use Ibexa\Core\Persistence\Legacy\Content\Type\Gateway;
use Ibexa\Core\Persistence\Legacy\Content\Type\Update\Handler;

/**
 * Doctrine database based type update handler.
 *
 * @internal For internal use by Repository
 */
final class DoctrineDatabase extends Handler
{
    /** @var \Ibexa\Core\Persistence\Legacy\Content\Type\Gateway */
    protected $contentTypeGateway;

    public function __construct(Gateway $contentTypeGateway)
    {
        $this->contentTypeGateway = $contentTypeGateway;
    }

    public function updateContentObjects(Type $fromType, Type $toType): void
    {
        // Do nothing, content objects are no longer updated
    }

    public function deleteOldType(Type $fromType): void
    {
        $this->contentTypeGateway->delete($fromType->id, $fromType->status);
    }

    public function publishNewType(Type $toType, int $newStatus): void
    {
        $this->contentTypeGateway->publishTypeAndFields(
            $toType->id,
            $toType->status,
            $newStatus
        );
    }
}
