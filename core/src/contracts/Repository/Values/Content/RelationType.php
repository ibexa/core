<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\Core\Repository\Values\Content;

enum RelationType: int
{
    /**
     * The relation type COMMON is a general relation between object set by a user.
     */
    case COMMON = 1;

    /**
     * the relation type EMBED is set for a relation which is anchored as embedded link in an attribute value.
     */
    case EMBED = 2;

    /**
     * the relation type LINK is set for a relation which is anchored as link in an attribute value.
     */
    case LINK = 4;

    /**
     * the relation type FIELD is set for a relation which is part of an relation attribute value.
     */
    case FIELD = 8;

    /**
     * the relation type ASSET is set for a relation to asset in an attribute value.
     */
    case ASSET = 16;
}
