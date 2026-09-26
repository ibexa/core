<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Bundle\RepositoryInstaller\Event\Subscriber\Fixtures\Entity;

final class Item
{
    public int $id;

    public Category $category;

    public string $name;
}
