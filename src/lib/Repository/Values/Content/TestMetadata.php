<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\Repository\Values\Content;

use Ibexa\Contracts\Core\Repository\Values\Content\Metadata as APIMetadata;

/**
 * this class represents a temporary Metadata.
 *
 * @internal Meant for internal use by Repository, type hint against API object instead.
 */
class TestMetadata extends APIMetadata
{
    protected string $identifier = 'test';

    /**
     * @var mixed
     */
    protected $value;
}

class_alias(TestMetadata::class, 'eZ\Publish\Core\Repository\Values\Content\TestMetadata');
