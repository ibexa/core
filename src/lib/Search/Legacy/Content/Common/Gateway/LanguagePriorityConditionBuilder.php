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

/**
 * Builds the "does $languageIdColumn hold the highest-priority translation this Content actually
 * has" condition shared by the Legacy Search Engine's field criterion (FieldBase) and field sort
 * clause (SortClauseHandler\Field).
 *
 * Historically this was expressed as bit-shift arithmetic directly on "c.language_mask" and
 * "$languageIdColumn" - a trick that only works because language ids are exact powers of two, with
 * no incremental replacement possible. The relational form instead asks "which of Content's actual
 * translations (via ibexa_content_translation) ranks highest in the requested priority list", via a
 * correlated `ORDER BY CASE ... LIMIT 1` subquery, and requires $languageIdColumn to equal that
 * pick (see {@see matchesLanguageId()} for a wrinkle in what "equal" means here). If none of
 * Content's translations are in the priority list, and the caller allowed the always-available
 * fallback (the default), an always-available Content instead matches on its main language -
 * mirroring the original arithmetic's use of "c.language_mask"'s bit 0.
 *
 * @internal
 */
final class LanguagePriorityConditionBuilder
{
    public function __construct(
        private readonly Connection $connection,
        private readonly LanguageHandler $languageHandler
    ) {
    }

    /**
     * @param array{languages?: string[], useAlwaysAvailable?: bool} $languageSettings
     */
    public function buildCondition(
        QueryBuilder $query,
        array $languageSettings,
        string $languageIdColumn,
        string $contentIdColumn = 'c.id',
        string $mainLanguageIdColumn = 'c.initial_language_id',
        string $alwaysAvailableColumn = 'c.always_available'
    ): string {
        if (empty($languageSettings['languages'])) {
            return $this->matchesLanguageId($query, $languageIdColumn, $mainLanguageIdColumn, $contentIdColumn);
        }

        $languageIds = array_map(
            fn (string $languageCode): int => $this->languageHandler->loadByLanguageCode($languageCode)->id,
            $languageSettings['languages']
        );

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
            ->from('ibexa_content_translation', 'ct')
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

        $priorityMatch = $this->matchesLanguageId($query, $languageIdColumn, sprintf('(%s)', $subQuery->getSQL()), $contentIdColumn);

        if (!($languageSettings['useAlwaysAvailable'] ?? true)) {
            return $priorityMatch;
        }

        // Content has none of the requested priority languages: an always-available Content falls
        // back to matching its main language, same as the empty-$languageSettings branch above.
        $hasRequestedLanguageSubQuery = $this->connection->createQueryBuilder();
        $hasRequestedLanguageSubQuery
            ->select('1')
            ->from('ibexa_content_translation', 'ct')
            ->where(
                $hasRequestedLanguageSubQuery->expr()->and(
                    sprintf('ct.content_id = %s', $contentIdColumn),
                    $hasRequestedLanguageSubQuery->expr()->in(
                        'ct.language_id',
                        $query->createNamedParameter($languageIds, ArrayParameterType::INTEGER)
                    )
                )
            );

        $alwaysAvailableFallback = $query->expr()->and(
            sprintf('NOT EXISTS (%s)', $hasRequestedLanguageSubQuery->getSQL()),
            $alwaysAvailableColumn,
            $this->matchesLanguageId($query, $languageIdColumn, $mainLanguageIdColumn, $contentIdColumn)
        );

        return (string)$query->expr()->or($priorityMatch, $alwaysAvailableFallback);
    }

    /**
     * Matches $languageIdColumn (e.g. ibexa_content_field.language_id) against $targetIdExpression
     * (a clean id, or an expression producing one, from ibexa_content_language/*_translation).
     *
     * $languageIdColumn may still carry the legacy "always available" bit 0 folded into it, from
     * rows written before always_available became a plain column - both on real installs upgrading
     * from that scheme and in long-lived test fixtures captured from it. Tolerate a "+1" tainted
     * value, but only when:
     * - the target id is even (only even ids are old-style; real ids were always powers of two, so
     *   an odd "+1" could only ever be that taint on an old install, never a genuinely different
     *   language on its own), and
     * - $languageIdColumn's raw value isn't itself one of Content's actual translations (via
     *   ibexa_content_translation) - otherwise a real, separate, newly-allocated odd-id language
     *   that happens to equal target+1 would be wrongly folded into target instead of matching
     *   itself.
     */
    private function matchesLanguageId(
        QueryBuilder $query,
        string $languageIdColumn,
        string $targetIdExpression,
        string $contentIdColumn
    ): string {
        $isGenuineTranslationSubQuery = $this->connection->createQueryBuilder();
        $isGenuineTranslationSubQuery
            ->select('1')
            ->from('ibexa_content_translation', 'ct_genuine')
            ->where(
                $isGenuineTranslationSubQuery->expr()->and(
                    sprintf('ct_genuine.content_id = %s', $contentIdColumn),
                    sprintf('ct_genuine.language_id = %s', $languageIdColumn)
                )
            );

        return (string) $query->expr()->or(
            $query->expr()->eq($languageIdColumn, $targetIdExpression),
            $query->expr()->and(
                sprintf('(%s) %% 2 = 0', $targetIdExpression),
                $query->expr()->eq($languageIdColumn, sprintf('((%s) + 1)', $targetIdExpression)),
                sprintf('NOT EXISTS (%s)', $isGenuineTranslationSubQuery->getSQL())
            )
        );
    }
}
