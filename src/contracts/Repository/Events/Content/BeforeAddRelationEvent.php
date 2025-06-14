<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\Core\Repository\Events\Content;

use Ibexa\Contracts\Core\Repository\Event\BeforeEvent;
use Ibexa\Contracts\Core\Repository\Values\Content\ContentInfo;
use Ibexa\Contracts\Core\Repository\Values\Content\Relation;
use Ibexa\Contracts\Core\Repository\Values\Content\VersionInfo;
use UnexpectedValueException;

final class BeforeAddRelationEvent extends BeforeEvent
{
    private VersionInfo $sourceVersion;

    private ContentInfo $destinationContent;

    private ?Relation $relation = null;

    public function __construct(VersionInfo $sourceVersion, ContentInfo $destinationContent)
    {
        $this->sourceVersion = $sourceVersion;
        $this->destinationContent = $destinationContent;
    }

    public function getSourceVersion(): VersionInfo
    {
        return $this->sourceVersion;
    }

    public function getDestinationContent(): ContentInfo
    {
        return $this->destinationContent;
    }

    public function getRelation(): Relation
    {
        if (!$this->hasRelation()) {
            throw new UnexpectedValueException(sprintf('Return value is not set or not of type %s. Check hasRelation() or set it using setRelation() before you call the getter.', Relation::class));
        }

        return $this->relation;
    }

    public function setRelation(?Relation $relation): void
    {
        $this->relation = $relation;
    }

    public function hasRelation(): bool
    {
        return $this->relation instanceof Relation;
    }
}
