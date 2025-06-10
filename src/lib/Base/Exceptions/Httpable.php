<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Core\Base\Exceptions;

/**
 * Interface for exceptions that maps to http status codes.
 *
 * The constants must be used as error code for this to be usable
 */
interface Httpable
{
    public const int BAD_REQUEST = 400;
    public const int UNAUTHORIZED = 401;
    public const int PAYMENT_REQUIRED = 402;
    public const int FORBIDDEN = 403;
    public const int NOT_FOUND = 404;
    public const int METHOD_NOT_ALLOWED = 405;
    public const int NOT_ACCEPTABLE = 406;
    public const int CONFLICT = 409;
    public const int GONE = 410;

    public const int UNSUPPORTED_MEDIA_TYPE = 415;

    public const int INTERNAL_ERROR = 500;
    public const int NOT_IMPLEMENTED = 501;
    public const int SERVICE_UNAVAILABLE = 503;
}
