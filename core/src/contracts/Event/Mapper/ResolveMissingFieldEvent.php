<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\Core\Event\Mapper;

use Ibexa\Contracts\Core\Persistence\Content;
use Ibexa\Contracts\Core\Persistence\Content\Field;
use Ibexa\Contracts\Core\Persistence\Content\Type\FieldDefinition;
use Symfony\Contracts\EventDispatcher\Event;

final class ResolveMissingFieldEvent extends Event
{
    private Content $content;

    private FieldDefinition $fieldDefinition;

    private string $languageCode;

    /** @var array<mixed> */
    private array $context;

    private ?Field $field;

    /**
     * @param array<mixed> $context
     */
    public function __construct(
        Content $content,
        FieldDefinition $fieldDefinition,
        string $languageCode,
        array $context = []
    ) {
        $this->content = $content;
        $this->fieldDefinition = $fieldDefinition;
        $this->languageCode = $languageCode;
        $this->context = $context;
        $this->field = null;
    }

    public function getContent(): Content
    {
        return $this->content;
    }

    public function getFieldDefinition(): FieldDefinition
    {
        return $this->fieldDefinition;
    }

    public function getLanguageCode(): string
    {
        return $this->languageCode;
    }

    /**
     * @return array<mixed>
     */
    public function getContext(): array
    {
        return $this->context;
    }

    public function setField(?Field $field): void
    {
        $this->field = $field;
    }

    public function getField(): ?Field
    {
        return $this->field;
    }
}
