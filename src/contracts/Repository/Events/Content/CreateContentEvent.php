<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\Core\Repository\Events\Content;

use Ibexa\Contracts\Core\Repository\Event\AfterEvent;
use Ibexa\Contracts\Core\Repository\Values\Content\Content;
use Ibexa\Contracts\Core\Repository\Values\Content\ContentCreateStruct;

final class CreateContentEvent extends AfterEvent
{
    private ContentCreateStruct $contentCreateStruct;

    /** @var \Ibexa\Contracts\Core\Repository\Values\Content\LocationCreateStruct[] */
    private array $locationCreateStructs;

    private Content $content;

    /** @var string[]|null */
    private ?array $fieldIdentifiersToValidate;

    /**
     * @param \Ibexa\Contracts\Core\Repository\Values\Content\LocationCreateStruct[]  $locationCreateStructs
     * @param string[]|null $fieldIdentifiersToValidate
     */
    public function __construct(
        Content $content,
        ContentCreateStruct $contentCreateStruct,
        array $locationCreateStructs,
        ?array $fieldIdentifiersToValidate = null
    ) {
        $this->content = $content;
        $this->contentCreateStruct = $contentCreateStruct;
        $this->locationCreateStructs = $locationCreateStructs;
        $this->fieldIdentifiersToValidate = $fieldIdentifiersToValidate;
    }

    public function getContentCreateStruct(): ContentCreateStruct
    {
        return $this->contentCreateStruct;
    }

    /**
     * @return \Ibexa\Contracts\Core\Repository\Values\Content\LocationCreateStruct[]
     */
    public function getLocationCreateStructs(): array
    {
        return $this->locationCreateStructs;
    }

    public function getContent(): Content
    {
        return $this->content;
    }

    /**
     * @return string[]|null
     */
    public function getFieldIdentifiersToValidate(): ?array
    {
        return $this->fieldIdentifiersToValidate;
    }
}
