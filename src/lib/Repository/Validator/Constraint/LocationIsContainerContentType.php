<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\Repository\Validator\Constraint;

use Symfony\Component\Validator\Constraint;

final class LocationIsContainerContentType extends Constraint
{
    protected const array ERROR_NAMES = [
        self::LOCATION_IS_NOT_CONTAINER_ERROR => 'LOCATION_IS_NOT_CONTAINER_ERROR',
    ];

    public const LOCATION_IS_NOT_CONTAINER_ERROR = '37dbce94-4365-4746-a9d2-533d54dad1bc';

    public string $message = 'Location with {{ contentTypeName }} is not a container content type.';

    /**
     * @param array<mixed>|null $options
     * @param array<string>|null $groups
     */
    public function __construct(
        array $options = null,
        string $message = null,
        array $groups = null,
        mixed $payload = null
    ) {
        parent::__construct($options ?? [], $groups, $payload);

        $this->message = $message ?? $this->message;
    }
}
