<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\Core\Pool;

use Ibexa\Core\Base\Exceptions\InvalidArgumentException;

/**
 * @template T of object
 *
 * @implements \Ibexa\Contracts\Core\Pool\PoolInterface<T>
 */
final class Pool implements PoolInterface
{
    private const DEFAULT_EXCEPTION_MESSAGE_TEMPLATE = 'Could not find %s for \'%s\'. Valid values are: %s';

    private string $class;

    /** @phpstan-var iterable<string,T> */
    private iterable $entries;

    private string $exceptionArgumentName = '$alias';

    private string $exceptionMessageTemplate = self::DEFAULT_EXCEPTION_MESSAGE_TEMPLATE;

    /**
     * @phpstan-param iterable<string,T> $entries
     */
    public function __construct(
        string $class,
        iterable $entries = []
    ) {
        $this->class = $class;
        $this->entries = $entries;
    }

    public function has(string $alias): bool
    {
        return $this->findEntry($alias) !== null;
    }

    /**
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException
     *
     * @phpstan-return T
     */
    public function get(string $alias)
    {
        $entry = $this->findEntry($alias);

        if ($entry === null) {
            $entriesAliases = $this->getEntriesAliases();
            $availableAliases = empty($entriesAliases) ? '' : "'" . implode("', '", $entriesAliases) . "'";

            throw new InvalidArgumentException(
                $this->exceptionArgumentName,
                sprintf($this->exceptionMessageTemplate, $this->class, $alias, $availableAliases)
            );
        }

        return $entry;
    }

    /**
     * @phpstan-return T|null
     */
    private function findEntry(string $needle)
    {
        foreach ($this->entries as $type => $mapper) {
            if ($needle === $type) {
                return $mapper;
            }
        }

        return null;
    }

    /**
     * @phpstan-return iterable<string,T>
     */
    public function getEntries(): iterable
    {
        return $this->entries;
    }

    /**
     * @return string[]
     */
    private function getEntriesAliases(): array
    {
        $aliases = [];
        foreach ($this->entries as $alias => $entry) {
            $aliases[] = $alias;
        }

        return $aliases;
    }

    public function setExceptionArgumentName(string $exceptionArgumentName): void
    {
        $this->exceptionArgumentName = $exceptionArgumentName;
    }

    public function setExceptionMessageTemplate(string $exceptionMessageTemplate): void
    {
        $this->exceptionMessageTemplate = $exceptionMessageTemplate;
    }
}
