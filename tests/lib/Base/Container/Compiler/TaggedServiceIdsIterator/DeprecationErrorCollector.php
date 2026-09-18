<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Core\Base\Container\Compiler\TaggedServiceIdsIterator;

/**
 * Captures user deprecation warnings emitted using trigger_error function.
 *
 * @internal
 */
final class DeprecationErrorCollector
{
    /** @var array */
    private $errors = [];

    public function register(): void
    {
        set_error_handler($this, E_USER_DEPRECATED);
    }

    public function unregister(): void
    {
        restore_error_handler();
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function __invoke(int $code, string $message, string $file, int $line): bool
    {
        $this->errors[] = [
            'code' => $code,
            'message' => $message,
            'file' => $file,
            'line' => $line,
        ];

        return true;
    }
}
