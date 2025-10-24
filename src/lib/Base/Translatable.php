<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\Base;

/**
 * Interface for translatable value objects.
 */
interface Translatable
{
    /**
     * Returns the message template, with placeholders for parameters.
     * E.g., "Content with ID %contentId% could not be found".
     */
    public function getMessageTemplate(): string;

    /**
     * Injects the message template.
     */
    public function setMessageTemplate(string $messageTemplate): void;

    /**
     * Returns a hash map with param placeholder as key and its corresponding value.
     * E.g., ```['%contentId%' => 123]```.
     *
     * @return array<string, mixed>
     */
    public function getParameters(): array;

    /**
     * Injects the hash map, with param placeholder as a key and its corresponding value.
     * E.g., ```['%contentId%' => 123]```.
     * If parameters already existed, they will be replaced by the passed here.
     *
     * @param array<string, mixed> $parameters
     */
    public function setParameters(array $parameters): void;

    /**
     * Adds a parameter to the existing hash map.
     */
    public function addParameter(
        string $name,
        string $value
    ): void;

    /**
     * Adds `$parameters` to an existing hash map.
     *
     * @param array<string, mixed> $parameters
     */
    public function addParameters(array $parameters): void;

    /**
     * Returns base translation, computed with message template and parameters.
     */
    public function getBaseTranslation(): string;
}
