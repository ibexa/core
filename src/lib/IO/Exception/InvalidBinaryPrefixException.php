<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\IO\Exception;

class InvalidBinaryPrefixException extends InvalidBinaryFileIdException
{
    public function __construct(string $identifier, string $prefix, int $code = 0)
    {
        parent::__construct($identifier);

        $this->setMessageTemplate(
            "Argument 'BinaryFile::id' is invalid: '%id%' is wrong value, it does not contain prefix '%prefix%'. Is 'var_dir' config correct?"
        );
        $this->setParameters(['%id%' => $identifier, '%prefix%' => $prefix]);

        // override message set by parent constructor chain
        $this->message = $this->getBaseTranslation();
        $this->code = $code;
    }
}
