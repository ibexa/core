<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\Core\Repository\Values\Content;

use DateTimeInterface;
use Ibexa\Contracts\Core\Repository\Values\ContentType\ContentType;

/**
 * This class is used for creating a new content object.
 *
 * @property Field[] $fields
 */
abstract class ContentCreateStruct extends ContentStruct
{
    /**
     * The content type for which the new content is created.
     *
     * Required.
     */
    public ContentType $contentType;

    /**
     * The main language code for the content. This language will also
     * be used as an initial language for the first created version.
     * It is also used as the default language for added fields.
     *
     * Required.
     */
    public string $mainLanguageCode;

    /**
     * The section to which the content is assigned.
     * If not set, either the parent section or a default section is used.
     */
    public ?int $sectionId = null;

    /**
     * The owner of the content. If not given, the current authenticated user is set as owner.
     */
    public ?int $ownerId = null;

    /**
     * Indicates if the content object is shown in the main language if it's not present in another requested language.
     */
    public ?bool $alwaysAvailable = null;

    /**
     * Remote identifier used as a custom identifier for the object.
     *
     * Needs to be a unique Content->remoteId string value.
     */
    public ?string $remoteId = null;

    /**
     * Modification date. If not given, the current timestamp is used.
     */
    public ?DateTimeInterface $modificationDate = null;
}
