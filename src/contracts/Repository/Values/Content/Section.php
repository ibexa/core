<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\Core\Repository\Values\Content;

use Ibexa\Contracts\Core\Repository\Values\ValueObject;

/**
 * This class represents a section.
 */
class Section extends ValueObject
{
    /**
     * Id of the section.
     *
     * @var mixed
     */
    protected $id;

    /**
     * Unique identifier of the section.
     *
     * @var string
     */
    protected $identifier;

    /**
     * Name of the section.
     *
     * @var string
     */
    protected $name;
}

class_alias(Section::class, 'eZ\Publish\API\Repository\Values\Content\Section');
