<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
namespace Ibexa\Contracts\Core\Repository\Values\Translation;

use Ibexa\Contracts\Core\Repository\Values\Translation;

/**
 * Class for translatable messages, which may contain plural forms.
 *
 * The message might include replacements, in the form <code>%[A-Za-z]%</code>. Those are
 * replaced by the values provided. A raw % can be escaped like %%.
 *
 * You need to provide a singular and plural variant for the string. The
 * strings provided should be English and will be translated depending on the
 * environment language.
 *
 * This interface follows the interfaces of XLIFF, gettext, Symfony Translations and Zend_Translate.
 * For singular forms you just provide a plain string (with optional placeholders without effects on the plural forms).
 * For potential plural forms you always provide a singular variant and an English simple plural variant.
 * An instance of this class can be cast to a string. In such case whether to use singular or plural form is determined
 * based on the value of first element of $values array (it needs to be 1 for singular, anything else for plural).
 * If plurality cannot be inferred from $values, a plural form is assumed as default. To force singular form,
 * use {@see \Ibexa\Contracts\Core\Repository\Values\Translation\Message} instead.
 *
 * No implementation supports multiple different plural forms in one single message.
 *
 * The singular / plural string could, for Symfony, for example be converted
 * to <code>"$singular|$plural"</code>, and you would call gettext like: <code>ngettext($singular, $plural, $count ).</code>
 */
class Plural extends Translation
{
    /**
     * Singular string. Might use replacements like %foo%, which are replaced by
     * the values specified in the values array.
     */
    protected string $singular;

    /**
     * Message string. Might use replacements like %foo%, which are replaced by
     * the values specified in the values array.
     */
    protected string $plural;

    /**
     * Translation value objects.
     *
     * @var array<string, scalar>
     */
    protected array $values;

    /**
     * Construct plural message from singular, plural and value array.
     *
     * @param array<string, scalar> $values
     */
    public function __construct(string $singular, string $plural, array $values)
    {
        $this->singular = $singular;
        $this->plural = $plural;
        $this->values = $values;

        parent::__construct();
    }

    public function __toString()
    {
        $firstValue = !empty($this->values) ? current(array_values($this->values)) : null;

        return strtr((int)$firstValue === 1 ? $this->singular : $this->plural, $this->values);
    }
}

class_alias(Plural::class, 'eZ\Publish\API\Repository\Values\Translation\Plural');
