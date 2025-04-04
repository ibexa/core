<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Bundle\Core\EventListener\Stubs;

use Symfony\Component\Console\Output\Output;

/**
 * Stub class for TestOutput Output.
 */
class TestOutput extends Output
{
    public $output = '';

    public function clear(): void
    {
        $this->output = '';
    }

    protected function doWrite($message, $newline)
    {
        $this->output .= $message . ($newline ? "\n" : '');
    }
}
