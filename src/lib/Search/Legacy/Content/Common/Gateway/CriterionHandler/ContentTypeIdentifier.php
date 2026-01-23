<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Core\Search\Legacy\Content\Common\Gateway\CriterionHandler;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\QueryBuilder;
use Ibexa\Contracts\Core\Persistence\Content\Type\Handler;
use Ibexa\Contracts\Core\Persistence\Content\Type\Handler as ContentTypeHandler;
use Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\CriterionInterface;
use Ibexa\Core\Search\Legacy\Content\Common\Gateway\CriteriaConverter;
use Ibexa\Core\Search\Legacy\Content\Common\Gateway\CriterionHandler;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Content type criterion handler.
 */
class ContentTypeIdentifier extends CriterionHandler
{
    /**
     * Content type handler.
     *
     * @var Handler
     */
    protected $contentTypeHandler;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    public function __construct(
        Connection $connection,
        ContentTypeHandler $contentTypeHandler,
        ?LoggerInterface $logger = null
    ) {
        parent::__construct($connection);

        $this->contentTypeHandler = $contentTypeHandler;
        $this->logger = $logger ?? new NullLogger();
    }

    public function accept(CriterionInterface $criterion): bool
    {
        return $criterion instanceof Criterion\ContentTypeIdentifier;
    }

    /**
     * @param Criterion\ContentTypeIdentifier $criterion
     */
    public function handle(
        CriteriaConverter $converter,
        QueryBuilder $queryBuilder,
        CriterionInterface $criterion,
        array $languageSettings
    ) {
        $idList = [];
        $invalidIdentifiers = [];

        foreach ($criterion->value as $identifier) {
            try {
                $idList[] = $this->contentTypeHandler->loadByIdentifier($identifier)->id;
            } catch (NotFoundException $e) {
                // Skip non-existing content types, but track for code below
                $invalidIdentifiers[] = $identifier;
            }
        }

        if (count($invalidIdentifiers) > 0) {
            $this->logger->warning(
                sprintf(
                    'Invalid content type identifiers provided for ContentTypeIdentifier criterion: %s',
                    implode(', ', $invalidIdentifiers)
                )
            );
        }

        if (count($idList) === 0) {
            return '1 = 0';
        }

        return $queryBuilder->expr()->in(
            'c.content_type_id',
            $queryBuilder->createNamedParameter($idList, Connection::PARAM_INT_ARRAY)
        );
    }
}
