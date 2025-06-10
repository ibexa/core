<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\Base\Exceptions;

use Ibexa\Contracts\Core\Repository\Exceptions\ContentTypeValidationException as APIContentTypeValidationException;
use Ibexa\Core\Base\Translatable;
use Ibexa\Core\Base\TranslatableBase;
use JMS\TranslationBundle\Annotation\Ignore;

/**
 * This Exception is thrown during content type creation or update when validation fails.
 */
class ContentTypeValidationException extends APIContentTypeValidationException implements Translatable
{
    use TranslatableBase;

    /**
     * @param string $messageTemplate The message template, with placeholders for parameters.
     *                                E.g. "Content with ID %contentId% could not be found".
     * @param array<string, mixed> $parameters Hash map with param placeholder as a key and its corresponding value.
     *                          E.g., ['%contentId%' => 123].
     */
    public function __construct(string $messageTemplate, array $parameters = [])
    {
        $this->setMessageTemplate(/** @Ignore */$messageTemplate);
        $this->setParameters($parameters);

        parent::__construct($this->getBaseTranslation());
    }
}
