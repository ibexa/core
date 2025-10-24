<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Core\Repository\Values\Content\Query\Criterion;

use Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion\Subtree as APISubtreeCriterion;

/**
 * Criterion that matches content that belongs to a given (list of) Subtree(s).
 *
 * Content will be matched if it is part of at least one of the given subtree path strings
 *
 * This is a internal subtree criterion intended for use by permission system (SubtreeLimitationType) only!
 * And will be applied by SQL based search engines on Content Search to avoid performance problems.
 *
 * @see https://issues.ibexa.co/browse/EZP-23037
 *
 * @internal Meant for internal use by Repository.
 */
class PermissionSubtree extends APISubtreeCriterion {}
