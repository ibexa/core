<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\Base\Exceptions;

use Exception;
use Ibexa\Core\Base\Translatable;
use Ibexa\Core\Base\TranslatableBase;
use LogicException;

/**
 * MissingClass Exception implementation.
 *
 * Usage:
 * ```
 * throw new MissingClass( $className, 'field type');
 * ```
 *
 * @todo Add a exception type in API that uses Logic exception and change this to extend it
 */
class MissingClass extends LogicException implements Translatable
{
    use TranslatableBase;

    /**
     * Generates: Could not find[ {$classType}] class '{$className}'.
     *
     * @param string|null $classType Optional string to specify what kind of class this is
     */
    public function __construct(
        string $className,
        ?string $classType = null,
        ?Exception $previous = null
    ) {
        $this->setParameters(['%className%' => $className]);
        if ($classType === null) {
            $this->setMessageTemplate("Could not find class '%className%'");
        } else {
            $this->setMessageTemplate("Could not find %classType% class '%className%'");
            $this->addParameter('%classType%', $classType);
        }

        parent::__construct($this->getBaseTranslation(), 0, $previous);
    }
}
