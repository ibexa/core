<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
namespace Ibexa\Core\Repository\Helper;

use Ibexa\Core\Repository\NameSchema\NameSchemaService as NativeNameSchemaService;

/**
 * NameSchemaService is internal service for resolving content name and url alias patterns.
 * This code supports content name pattern groups.
 *
 * Syntax:
 * <code>
 * &lt;attribute_identifier&gt;
 * &lt;attribute_identifier&gt; &lt;2nd-identifier&gt;
 * User text &lt;attribute_identifier&gt;|(&lt;2nd-identifier&gt;&lt;3rd-identifier&gt;)
 * </code>
 *
 * Example:
 * <code>
 * &lt;nickname|(&lt;firstname&gt; &lt;lastname&gt;)&gt;
 * </code>
 *
 * Tokens are looked up from left to right. If a match is found for the
 * leftmost token, the 2nd token will not be used. Tokens are representations
 * of fields. So a match means that that the current field has data.
 *
 * Tokens are the field definition identifiers which are used in the class edit-interface.
 *
 * @internal Meant for internal use by Repository.
 *
 * @deprecated inject \Ibexa\Contracts\Core\Repository\NameSchema\NameSchemaServiceInterface instead.
 * @see \Ibexa\Contracts\Core\Repository\NameSchema\NameSchemaServiceInterface
 */
final class NameSchemaService extends NativeNameSchemaService
{
}

class_alias(NameSchemaService::class, 'eZ\Publish\Core\Repository\Helper\NameSchemaService');
