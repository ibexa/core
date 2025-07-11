<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\Base\Exceptions;

use Exception;
use Ibexa\Contracts\Core\Repository\Exceptions\UnauthorizedException as APIUnauthorizedException;
use Ibexa\Core\Base\Translatable;
use Ibexa\Core\Base\TranslatableBase;

/**
 * UnauthorizedException Exception implementation.
 *
 * Usage:
 * ```
 * throw new UnauthorizedException('content', 'read', ['contentId' => 42]);
 * ```
 */
class UnauthorizedException extends APIUnauthorizedException implements Httpable, Translatable
{
    use TranslatableBase;

    /**
     * Generates: User does not have access to '{$function}' '{$module}'[ with: %property.key% '%property.value%'].
     *
     * Example: User does not have access to 'read' 'content' with: id '44', type 'article'
     *
     * @param string $module The module name should be in sync with the name of the domain object in question
     *
     * @phpstan-param array<string, scalar|\Stringable|null>|null $properties Key value pair with non-sensitive data on what kind of data user does not have access to
     */
    public function __construct(
        string $module,
        string $function,
        ?array $properties = null,
        ?Exception $previous = null
    ) {
        $this->setMessageTemplate("The User does not have the '%function%' '%module%' permission");
        $this->setParameters(['%module%' => $module, '%function%' => $function]);

        if (!empty($properties)) {
            $this->setMessageTemplate("The User does not have the '%function%' '%module%' permission with: %with%");
            $with = [];
            foreach ($properties as $name => $value) {
                $with[] = "$name '$value'";
            }
            $this->addParameter('%with%', implode(', ', $with));
        }

        parent::__construct($this->getBaseTranslation(), self::UNAUTHORIZED, $previous);
    }
}
