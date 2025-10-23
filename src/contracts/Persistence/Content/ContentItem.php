<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\Core\Persistence\Content;

use Ibexa\Contracts\Core\Persistence\Content;
use Ibexa\Contracts\Core\Persistence\ValueObject;

/**
 * Content item Value Object - a composite of Content and Type instances.
 *
 * @property-read Content $content
 * @property-read ContentInfo $contentInfo
 * @property-read Type $type
 */
final class ContentItem extends ValueObject
{
    /** @var Content */
    protected $content;

    /** @var ContentInfo */
    protected $contentInfo;

    /** @var Type */
    protected $type;

    /**
     * @internal for internal use by Repository Storage abstraction
     */
    public function __construct(
        Content $content,
        ContentInfo $contentInfo,
        Type $type
    ) {
        parent::__construct([]);
        $this->content = $content;
        $this->contentInfo = $contentInfo;
        $this->type = $type;
    }

    public function getContent(): Content
    {
        return $this->content;
    }

    public function getContentInfo(): ContentInfo
    {
        return $this->contentInfo;
    }

    public function getType(): Type
    {
        return $this->type;
    }
}
