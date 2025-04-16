<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Bundle\Core\EventListener\Stubs;

use Symfony\Component\Console\Output\Output;

/**
 * Stub class for TestOutput Output.
 */
class TestOutput extends Output
{
    public string $output = '';

    public function clear(): void
    {
        $this->output = '';
    }

    protected function doWrite(string $message, bool $newline): void
    {
        $this->output .= $message . ($newline ? "\n" : '');
    }
}
