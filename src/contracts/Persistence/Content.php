<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Contracts\Core\Persistence;

use Ibexa\Contracts\Core\Persistence\Content\Field;
use Ibexa\Contracts\Core\Persistence\Content\VersionInfo;

/**
 * Content value object, bound to a version.
 * This object aggregates the following:
 *  - Version metadata
 *  - Content metadata
 *  - Fields.
 */
class Content extends ValueObject
{
    /**
     * VersionInfo object for this content's version.
     *
     * @var VersionInfo
     */
    public $versionInfo;

    /**
     * Field objects for this content.
     *
     * @var Field[]
     */
    public $fields;
}
