<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\Core\Repository\Values\ContentType;

use DateTimeInterface;
use Ibexa\Contracts\Core\Repository\Values\ValueObject;

/**
 * This class is used for updating a content type.
 */
class ContentTypeUpdateStruct extends ValueObject
{
    /**
     * If set the unique identifier of a type is changed to this value.
     */
    public ?string $identifier = null;

    /**
     * If set the remote ID is changed to this value.
     */
    public ?string $remoteId = null;

    /**
     * If set the URL alias schema is changed to this value.
     */
    public ?string $urlAliasSchema = null;

    /**
     * If set the name schema is changed to this value.
     */
    public ?string $nameSchema = null;

    /**
     * If set the container flag is set to this value.
     */
    public ?bool $isContainer = null;

    /**
     * If set the main language is changed to this value.
     */
    public ?string $mainLanguageCode = null;

    /**
     * If set the default sort field is changed to this value.
     */
    public ?int $defaultSortField = null;

    /**
     * If set the default sort order is set to this value.
     */
    public ?int $defaultSortOrder = null;

    /**
     * If set the default always available flag is set to this value.
     */
    public ?bool $defaultAlwaysAvailable = null;

    /**
     * If set this value overrides the current user as creator.
     */
    public ?int $modifierId = null;

    /**
     * If set this value overrides the current time for creation.
     */
    public ?DateTimeInterface $modificationDate = null;

    /**
     * If set this array of names with languageCode keys replace the complete name collection.
     *
     * @var array<string, string> an array of string
     */
    public array $names = [];

    /**
     * If set this array of descriptions with languageCode keys replace the complete description collection.
     *
     * @var array<string, string> an array of string
     */
    public array $descriptions = [];
}
