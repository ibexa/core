<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\Core\Repository\Values\ContentType;

use Ibexa\Contracts\Core\Repository\Values\MultiLanguageDescription;
use Ibexa\Contracts\Core\Repository\Values\MultiLanguageName;
use Ibexa\Contracts\Core\Repository\Values\ValueObject;

/**
 * This class represents a content type group value.
 */
abstract class ContentTypeGroup extends ValueObject implements MultiLanguageName, MultiLanguageDescription
{
    /**
     * Primary key.
     *
     * @var mixed
     */
    protected $id;

    /**
     * Readable string identifier of a group.
     *
     * @var string
     */
    protected $identifier;

    /**
     * Created date (timestamp).
     *
     * @var \DateTime
     */
    protected $creationDate;

    /**
     * Modified date (timestamp).
     *
     * @var \DateTime
     */
    protected $modificationDate;

    /**
     * Creator user id.
     *
     * @var mixed
     */
    protected $creatorId;

    /**
     * Modifier user id.
     *
     * @var mixed
     */
    protected $modifierId;

    /**
     * @var bool
     */
    public $isSystem = false;
}

class_alias(ContentTypeGroup::class, 'eZ\Publish\API\Repository\Values\ContentType\ContentTypeGroup');
