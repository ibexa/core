<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Core\QueryType;

use Ibexa\Contracts\Core\Repository\Values\Content\Query;
use Ibexa\Core\MVC\Symfony\View\ContentView;

/**
 * Maps a ContentView to a QueryType.
 */
interface ContentViewQueryTypeMapper
{
    public function map(ContentView $contentView): Query;
}
