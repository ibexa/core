<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\MVC\Exception;

use Ibexa\Contracts\Core\Repository\Values\Content\Location;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

class HiddenLocationException extends NotFoundHttpException
{
    private Location $location;

    public function __construct(Location $location, ?string $message = null, ?Throwable $previous = null, int $code = 0)
    {
        $this->location = $location;
        parent::__construct($message ?? 'HTTP Not Found', $previous, $code);
    }

    public function getLocation(): Location
    {
        return $this->location;
    }
}
