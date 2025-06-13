<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\IO\Exception;

class InvalidBinaryAbsolutePathException extends InvalidBinaryFileIdException
{
    public function __construct(string $identifier, int $code = 0)
    {
        parent::__construct($identifier);

        $this->setMessageTemplate(
            "Argument 'BinaryFile::id' is invalid: '%id%' is wrong value, binary file ids can not begin with a '/'"
        );
        $this->setParameters(['%id%' => $identifier]);

        // override message set by parent constructor chain
        $this->message = $this->getBaseTranslation();
        $this->code = $code;
    }
}
