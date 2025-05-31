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
 * @property \Ibexa\Contracts\Core\Repository\Values\Content\Field[] $fields
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
     * the main language code for the content. This language will also
     * be used for as initial language for the first created version.
     * It is also used as default language for added fields.
     *
     * Required.
     */
    public string $mainLanguageCode;

    /**
     * The section the content is assigned to.
     * If not set the section of the parent is used or a default section.
     */
    public ?int $sectionId = null;

    /**
     * The owner of the content. If not given the current authenticated user is set as owner.
     */
    public ?int $ownerId = null;

    /**
     * Indicates if the content object is shown in the mainlanguage if its not present in an other requested language.
     */
    public ?bool $alwaysAvailable = null;

    /**
     * Remote identifier used as a custom identifier for the object.
     *
     * Needs to be a unique Content->remoteId string value.
     */
    public ?string $remoteId = null;

    /**
     * Modification date. If not given the current timestamp is used.
     */
    public ?DateTimeInterface $modificationDate = null;
}
