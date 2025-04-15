<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Core\Search\Legacy\Content\Common\Gateway\CriterionHandler;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\QueryBuilder;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\CriterionInterface;
use Ibexa\Core\Persistence\Legacy\Content\Language\MaskGenerator;
use Ibexa\Core\Search\Legacy\Content\Common\Gateway\CriteriaConverter;
use Ibexa\Core\Search\Legacy\Content\Common\Gateway\CriterionHandler;

/**
 * LanguageCode criterion handler.
 */
class LanguageCode extends CriterionHandler
{
    private MaskGenerator $maskGenerator;

    public function __construct(Connection $connection, MaskGenerator $maskGenerator)
    {
        parent::__construct($connection);

        $this->maskGenerator = $maskGenerator;
    }

    public function accept(CriterionInterface $criterion): bool
    {
        return $criterion instanceof Criterion\LanguageCode;
    }

    /**
     * @param \Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion\LanguageCode $criterion
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException
     */
    public function handle(
        CriteriaConverter $converter,
        QueryBuilder $queryBuilder,
        CriterionInterface $criterion,
        array $languageSettings
    ): string {
        /* @var $criterion \Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion\LanguageCode */
        return $queryBuilder->expr()->gt(
            $this->dbPlatform->getBitAndComparisonExpression(
                'c.language_mask',
                $this->maskGenerator->generateLanguageMaskFromLanguageCodes(
                    $criterion->value,
                    $criterion->matchAlwaysAvailable
                )
            ),
            0
        );
    }
}
