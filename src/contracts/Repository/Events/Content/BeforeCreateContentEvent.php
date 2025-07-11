<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\Core\Repository\Events\Content;

use Ibexa\Contracts\Core\Repository\Event\BeforeEvent;
use Ibexa\Contracts\Core\Repository\Values\Content\Content;
use Ibexa\Contracts\Core\Repository\Values\Content\ContentCreateStruct;
use UnexpectedValueException;

final class BeforeCreateContentEvent extends BeforeEvent
{
    private ContentCreateStruct $contentCreateStruct;

    /** @var \Ibexa\Contracts\Core\Repository\Values\Content\LocationCreateStruct[] */
    private array $locationCreateStructs;

    private ?Content $content = null;

    /** @var string[]|null */
    private ?array $fieldIdentifiersToValidate;

    public function __construct(
        ContentCreateStruct $contentCreateStruct,
        array $locationCreateStructs,
        ?array $fieldIdentifiersToValidate = null
    ) {
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

    /**
     * @return string[]|null
     */
    public function getFieldIdentifiersToValidate(): ?array
    {
        return $this->fieldIdentifiersToValidate;
    }

    public function getContent(): Content
    {
        if (!$this->hasContent()) {
            throw new UnexpectedValueException(sprintf('Return value is not set or not of type %s. Check hasContent() or set it using setContent() before you call the getter.', Content::class));
        }

        return $this->content;
    }

    public function setContent(?Content $content): void
    {
        $this->content = $content;
    }

    public function hasContent(): bool
    {
        return $this->content instanceof Content;
    }
}
