<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\Core\Repository\Values\Translation;

use Ibexa\Contracts\Core\Repository\Values\Translation;

/**
 * Class for translatable messages, which only occur in singular form.
 *
 * The message might include replacements, in the form %[A-Za-z]%. Those are
 * replaced by the values provided. A raw % can be escaped like %%.
 */
class Message extends Translation
{
    /**
     * Message string. Might use replacements like %foo%, which are replaced by
     * the values specified in the `$values` array.
     */
    protected string $message;

    /**
     * Translation value objects. May not contain any numbers, which might
     * result in requiring plural forms. Use `Plural` class for that.
     *
     * @see Plural
     *
     * @var array<string, scalar|null>
     */
    protected array $values;

    /**
     * Construct a singular only message from string and optional value array.
     *
     * @param array<string, scalar|null> $values
     */
    public function __construct(
        string $message,
        array $values = []
    ) {
        $this->message = $message;
        $this->values = $values;

        parent::__construct();
    }

    #[\Override]
    public function __toString(): string
    {
        return strtr($this->message, $this->values);
    }
}
