<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Bundle\Core\Imagine\VariationPurger;

use Countable;
use Iterator;

/**
 * Iterates over BinaryFile id entries for original images.
 *
 * @extends \Iterator<array-key, string>
 */
interface ImageFileList extends Countable, Iterator {}
