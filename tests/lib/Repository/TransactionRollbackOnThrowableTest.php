<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Core\Repository;

use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/**
 * Guards that a try block rolling back a transaction also rolls back on \Error,
 * otherwise a long-running process (Messenger consumer, persistent worker) keeps the transaction open.
 */
final class TransactionRollbackOnThrowableTest extends TestCase
{
    private const SCANNED_DIRECTORIES = [
        __DIR__ . '/../../../src/lib/Repository',
        __DIR__ . '/../../../src/lib/Persistence',
    ];

    public function testTryBlocksRollingBackCatchThrowable(): void
    {
        $scannedFiles = 0;
        $violations = [];
        foreach (self::SCANNED_DIRECTORIES as $directory) {
            $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory, RecursiveDirectoryIterator::SKIP_DOTS));
            foreach ($files as $file) {
                if ($file->getExtension() !== 'php') {
                    continue;
                }

                ++$scannedFiles;
                foreach ($this->findViolations((string)file_get_contents($file->getPathname())) as $line) {
                    $violations[] = $file->getPathname() . ':' . $line;
                }
            }
        }

        self::assertGreaterThan(0, $scannedFiles);
        self::assertSame([], $violations, 'Try blocks rolling back a transaction must also catch \Throwable');
    }

    /**
     * @return int[] lines of try statements whose catch blocks roll back but none of them catches Throwable
     */
    private function findViolations(string $source): array
    {
        $tokens = token_get_all($source);
        $violations = [];
        foreach ($tokens as $index => $token) {
            if (!is_array($token) || $token[0] !== T_TRY) {
                continue;
            }

            $position = $this->skipBlock($tokens, $index);
            $rollsBack = false;
            $catchesThrowable = false;
            while (null !== ($next = $this->nextSignificant($tokens, $position)) && is_array($tokens[$next]) && $tokens[$next][0] === T_CATCH) {
                $types = '';
                for ($position = $next + 1; $tokens[$position] !== ')'; ++$position) {
                    $types .= is_array($tokens[$position]) ? $tokens[$position][1] : $tokens[$position];
                }
                $catchesThrowable = $catchesThrowable || preg_match('/(^|[\s(|\\\\])Throwable\b/', $types) === 1;

                // nested try statements are checked on their own
                $body = '';
                $end = $this->skipBlock($tokens, $position);
                for ($i = $position; $i < $end; ++$i) {
                    if (is_array($tokens[$i]) && $tokens[$i][0] === T_TRY) {
                        $i = $this->skipTryStatement($tokens, $i) - 1;
                        continue;
                    }
                    $body .= is_array($tokens[$i]) ? $tokens[$i][1] : $tokens[$i];
                }
                $rollsBack = $rollsBack || preg_match('/->roll[bB]ack\s*\(/', $body) === 1;
                $position = $end;
            }

            if ($rollsBack && !$catchesThrowable) {
                $violations[] = $token[2];
            }
        }

        return $violations;
    }

    /**
     * @param array<int, mixed> $tokens
     *
     * @return int index right after the last catch/finally block of the try statement at $index
     */
    private function skipTryStatement(
        array $tokens,
        int $index
    ): int {
        $position = $this->skipBlock($tokens, $index);
        while (null !== ($next = $this->nextSignificant($tokens, $position))
            && is_array($tokens[$next])
            && in_array($tokens[$next][0], [T_CATCH, T_FINALLY], true)
        ) {
            $position = $this->skipBlock($tokens, $next);
        }

        return $position;
    }

    /**
     * @param array<int, mixed> $tokens
     *
     * @return int index right after the closing brace of the first block starting at or after $index
     */
    private function skipBlock(
        array $tokens,
        int $index
    ): int {
        while ($tokens[$index] !== '{') {
            ++$index;
        }

        $depth = 0;
        do {
            $token = $tokens[$index];
            if ($token === '{' || (is_array($token) && in_array($token[0], [T_CURLY_OPEN, T_DOLLAR_OPEN_CURLY_BRACES], true))) {
                ++$depth;
            } elseif ($token === '}') {
                --$depth;
            }
            ++$index;
        } while ($depth > 0);

        return $index;
    }

    /**
     * @param array<int, mixed> $tokens
     */
    private function nextSignificant(
        array $tokens,
        int $index
    ): ?int {
        for ($count = count($tokens); $index < $count; ++$index) {
            if (!is_array($tokens[$index]) || !in_array($tokens[$index][0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true)) {
                return $index;
            }
        }

        return null;
    }
}
