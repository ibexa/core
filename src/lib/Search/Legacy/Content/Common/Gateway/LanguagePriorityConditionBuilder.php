<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\Search\Legacy\Content\Common\Gateway;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Query\QueryBuilder;
use Ibexa\Contracts\Core\Persistence\Content\Language\Handler as LanguageHandler;
use Ibexa\Core\Base\Exceptions\NotFoundException;
use Ibexa\Core\Persistence\Legacy\Content\Gateway as ContentGateway;

/**
 * Builds the condition matching the field language a Content is shown in for a language priority list.
 *
 * @internal
 */
final readonly class LanguagePriorityConditionBuilder
{
    /**
     * Pre-computed integer constant which, when combined with a bit-wise AND, removes the
     * always-available bit from a language id.
     */
    private const int REMOVE_ALWAYS_AVAILABLE_BIT_OPERAND = -2;

    public function __construct(
        private Connection $connection,
        private LanguageHandler $languageHandler
    ) {}

    /**
     * @param array{languages?: string[]} $languageSettings
     */
    public function buildCondition(
        QueryBuilder $query,
        array $languageSettings,
        string $languageIdColumn,
        string $contentIdColumn = 'c.id',
        string $mainLanguageIdColumn = 'c.initial_language_id',
        string $alwaysAvailableColumn = 'c.always_available'
    ): string {
        // Field language ids still carry the legacy "always available" bit until it's stripped
        // together with the language bitmask - compare them without it.
        $languageIdColumn = $this->connection->getDatabasePlatform()->getBitAndComparisonExpression(
            $languageIdColumn,
            (string)self::REMOVE_ALWAYS_AVAILABLE_BIT_OPERAND
        );

        return $query->expr()->eq(
            $languageIdColumn,
            $this->buildMatchedLanguageExpression(
                $query,
                $languageSettings,
                $contentIdColumn,
                $mainLanguageIdColumn,
                $alwaysAvailableColumn
            )
        );
    }

    /**
     * Sets the rows' "matched_language_id" to their Content's first translation in the language filter.
     *
     * @param list<array<string, mixed>> $rows
     * @param array{languages?: string[]} $languageFilter
     *
     * @return list<array<string, mixed>>
     *
     * @throws NotFoundException
     */
    public function resolveMatchedLanguageIds(
        array $rows,
        array $languageFilter,
        string $contentIdColumn
    ): array {
        if (empty($rows) || empty($languageFilter['languages'])) {
            return $rows;
        }

        $priorities = array_flip($this->loadLanguageIds($languageFilter['languages']));
        $contentIds = array_values(array_unique(array_map('intval', array_column($rows, $contentIdColumn))));

        $query = $this->connection->createQueryBuilder();
        $query
            ->select('content_id', 'language_id')
            ->from(ContentGateway::CONTENT_TRANSLATION_TABLE)
            ->where(
                $query->expr()->in(
                    'content_id',
                    $query->createNamedParameter($contentIds, ArrayParameterType::INTEGER)
                ),
                $query->expr()->in(
                    'language_id',
                    $query->createNamedParameter(array_keys($priorities), ArrayParameterType::INTEGER)
                )
            );

        $matchedLanguageIds = [];
        foreach ($query->executeQuery()->iterateNumeric() as [$contentId, $languageId]) {
            $matchedLanguageId = $matchedLanguageIds[(int)$contentId] ?? null;
            if ($matchedLanguageId === null || $priorities[(int)$languageId] < $priorities[$matchedLanguageId]) {
                $matchedLanguageIds[(int)$contentId] = (int)$languageId;
            }
        }

        return array_map(
            static fn (array $row): array => [
                'matched_language_id' => $matchedLanguageIds[(int)$row[$contentIdColumn]] ?? $row['matched_language_id'],
            ] + $row,
            $rows
        );
    }

    /**
     * Builds the expression for the id of the language a Content is shown in for a language priority list.
     *
     * @param array{languages?: string[]} $languageSettings
     */
    private function buildMatchedLanguageExpression(
        QueryBuilder $query,
        array $languageSettings,
        string $contentIdColumn = 'c.id',
        string $mainLanguageIdColumn = 'c.initial_language_id',
        string $alwaysAvailableColumn = 'c.always_available'
    ): string {
        if (empty($languageSettings['languages'])) {
            return $mainLanguageIdColumn;
        }

        $languageIds = $this->loadLanguageIds($languageSettings['languages']);

        $priorityCase = 'CASE ct.language_id';
        foreach ($languageIds as $priority => $languageId) {
            $priorityCase .= sprintf(
                ' WHEN %s THEN %d',
                $query->createNamedParameter($languageId, ParameterType::INTEGER),
                $priority
            );
        }
        $priorityCase .= ' END';

        $subQuery = $this->connection->createQueryBuilder();
        $subQuery
            ->select('ct.language_id')
            ->from(ContentGateway::CONTENT_TRANSLATION_TABLE, 'ct')
            ->where(
                $subQuery->expr()->and(
                    sprintf('ct.content_id = %s', $contentIdColumn),
                    $subQuery->expr()->in(
                        'ct.language_id',
                        $query->createNamedParameter($languageIds, ArrayParameterType::INTEGER)
                    )
                )
            )
            ->orderBy($priorityCase)
            ->setMaxResults(1);

        return sprintf(
            'COALESCE((%s), CASE WHEN %s THEN %s END)',
            $subQuery->getSQL(),
            $alwaysAvailableColumn,
            $mainLanguageIdColumn
        );
    }

    /**
     * Loads the ids of the given languages, in the order of their codes.
     *
     * @param string[] $languageCodes
     *
     * @return list<int>
     *
     * @throws NotFoundException
     */
    public function loadLanguageIds(array $languageCodes): array
    {
        $languages = iterator_to_array($this->languageHandler->loadListByLanguageCodes($languageCodes));
        $missingLanguageCodes = array_diff($languageCodes, array_keys($languages));
        if (!empty($missingLanguageCodes)) {
            throw new NotFoundException('Language', implode(', ', $missingLanguageCodes));
        }

        return array_map(
            static fn (string $languageCode): int => $languages[$languageCode]->id,
            array_values($languageCodes)
        );
    }

    /**
     * Builds the condition matching Content translated into any of the given languages.
     *
     * @param int[] $languageIds
     */
    public function buildTranslationCondition(
        QueryBuilder $query,
        array $languageIds,
        bool $matchAlwaysAvailable,
        string $contentIdColumn = 'c.id',
        string $alwaysAvailableColumn = 'c.always_available'
    ): string {
        $subQuery = $this->connection->createQueryBuilder();
        $subQuery
            ->select('1')
            ->from(ContentGateway::CONTENT_TRANSLATION_TABLE, 'ct')
            ->where(
                $subQuery->expr()->and(
                    sprintf('ct.content_id = %s', $contentIdColumn),
                    $subQuery->expr()->in(
                        'ct.language_id',
                        $query->createNamedParameter($languageIds, ArrayParameterType::INTEGER)
                    )
                )
            );

        $condition = sprintf('EXISTS (%s)', $subQuery->getSQL());

        if (!$matchAlwaysAvailable) {
            return $condition;
        }

        return (string)$query->expr()->or(
            $condition,
            $query->expr()->eq(
                $alwaysAvailableColumn,
                $query->createNamedParameter(true, ParameterType::BOOLEAN)
            )
        );
    }
}
