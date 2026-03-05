<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\Persistence\Legacy\Content\ObjectState\Gateway;

use Doctrine\DBAL\DBALException;
use Ibexa\Contracts\Core\Persistence\Content\ObjectState;
use Ibexa\Contracts\Core\Persistence\Content\ObjectState\Group;
use Ibexa\Core\Base\Exceptions\DatabaseException;
use Ibexa\Core\Persistence\Legacy\Content\ObjectState\Gateway;
use PDOException;
use Throwable;

/**
 * @internal Internal exception conversion layer.
 */
final class ExceptionConversion extends Gateway
{
    /**
     * The wrapped gateway.
     *
     * @var \Ibexa\Core\Persistence\Legacy\Content\ObjectState\Gateway
     */
    private $innerGateway;

    /**
     * Creates a new exception conversion gateway around $innerGateway.
     *
     * @param \Ibexa\Core\Persistence\Legacy\Content\ObjectState\Gateway $innerGateway
     */
    public function __construct(Gateway $innerGateway)
    {
        $this->innerGateway = $innerGateway;
    }

    public function loadObjectStateData(int $stateId): array
    {
        try {
            return $this->innerGateway->loadObjectStateData($stateId);
        } catch (Throwable $e) {
            if ($e instanceof DBALException || $e instanceof PDOException) {
                throw DatabaseException::wrap($e);
            }

            throw $e;
        }
    }

    public function loadObjectStateDataByIdentifier(string $identifier, int $groupId): array
    {
        try {
            return $this->innerGateway->loadObjectStateDataByIdentifier($identifier, $groupId);
        } catch (Throwable $e) {
            if ($e instanceof DBALException || $e instanceof PDOException) {
                throw DatabaseException::wrap($e);
            }

            throw $e;
        }
    }

    public function loadObjectStateListData(int $groupId): array
    {
        try {
            return $this->innerGateway->loadObjectStateListData($groupId);
        } catch (Throwable $e) {
            if ($e instanceof DBALException || $e instanceof PDOException) {
                throw DatabaseException::wrap($e);
            }

            throw $e;
        }
    }

    public function loadObjectStateGroupData(int $groupId): array
    {
        try {
            return $this->innerGateway->loadObjectStateGroupData($groupId);
        } catch (Throwable $e) {
            if ($e instanceof DBALException || $e instanceof PDOException) {
                throw DatabaseException::wrap($e);
            }

            throw $e;
        }
    }

    public function loadObjectStateGroupDataByIdentifier(string $identifier): array
    {
        try {
            return $this->innerGateway->loadObjectStateGroupDataByIdentifier($identifier);
        } catch (Throwable $e) {
            if ($e instanceof DBALException || $e instanceof PDOException) {
                throw DatabaseException::wrap($e);
            }

            throw $e;
        }
    }

    public function loadObjectStateGroupListData(int $offset, int $limit): array
    {
        try {
            return $this->innerGateway->loadObjectStateGroupListData($offset, $limit);
        } catch (Throwable $e) {
            if ($e instanceof DBALException || $e instanceof PDOException) {
                throw DatabaseException::wrap($e);
            }

            throw $e;
        }
    }

    public function insertObjectState(ObjectState $objectState, int $groupId): void
    {
        try {
            $this->innerGateway->insertObjectState($objectState, $groupId);
        } catch (Throwable $e) {
            if ($e instanceof DBALException || $e instanceof PDOException) {
                throw DatabaseException::wrap($e);
            }

            throw $e;
        }
    }

    public function updateObjectState(ObjectState $objectState): void
    {
        try {
            $this->innerGateway->updateObjectState($objectState);
        } catch (Throwable $e) {
            if ($e instanceof DBALException || $e instanceof PDOException) {
                throw DatabaseException::wrap($e);
            }

            throw $e;
        }
    }

    public function deleteObjectState(int $stateId): void
    {
        try {
            $this->innerGateway->deleteObjectState($stateId);
        } catch (Throwable $e) {
            if ($e instanceof DBALException || $e instanceof PDOException) {
                throw DatabaseException::wrap($e);
            }

            throw $e;
        }
    }

    public function updateObjectStateLinks(int $oldStateId, int $newStateId): void
    {
        try {
            $this->innerGateway->updateObjectStateLinks($oldStateId, $newStateId);
        } catch (Throwable $e) {
            if ($e instanceof DBALException || $e instanceof PDOException) {
                throw DatabaseException::wrap($e);
            }

            throw $e;
        }
    }

    public function deleteObjectStateLinks(int $stateId): void
    {
        try {
            $this->innerGateway->deleteObjectStateLinks($stateId);
        } catch (Throwable $e) {
            if ($e instanceof DBALException || $e instanceof PDOException) {
                throw DatabaseException::wrap($e);
            }

            throw $e;
        }
    }

    public function insertObjectStateGroup(Group $objectStateGroup): void
    {
        try {
            $this->innerGateway->insertObjectStateGroup($objectStateGroup);
        } catch (Throwable $e) {
            if ($e instanceof DBALException || $e instanceof PDOException) {
                throw DatabaseException::wrap($e);
            }

            throw $e;
        }
    }

    public function updateObjectStateGroup(Group $objectStateGroup): void
    {
        try {
            $this->innerGateway->updateObjectStateGroup($objectStateGroup);
        } catch (Throwable $e) {
            if ($e instanceof DBALException || $e instanceof PDOException) {
                throw DatabaseException::wrap($e);
            }

            throw $e;
        }
    }

    public function deleteObjectStateGroup(int $groupId): void
    {
        try {
            $this->innerGateway->deleteObjectStateGroup($groupId);
        } catch (Throwable $e) {
            if ($e instanceof DBALException || $e instanceof PDOException) {
                throw DatabaseException::wrap($e);
            }

            throw $e;
        }
    }

    public function setContentState(int $contentId, int $groupId, int $stateId): void
    {
        try {
            $this->innerGateway->setContentState($contentId, $groupId, $stateId);
        } catch (Throwable $e) {
            if ($e instanceof DBALException || $e instanceof PDOException) {
                throw DatabaseException::wrap($e);
            }

            throw $e;
        }
    }

    public function loadObjectStateDataForContent(int $contentId, int $stateGroupId): array
    {
        try {
            return $this->innerGateway->loadObjectStateDataForContent($contentId, $stateGroupId);
        } catch (Throwable $e) {
            if ($e instanceof DBALException || $e instanceof PDOException) {
                throw DatabaseException::wrap($e);
            }

            throw $e;
        }
    }

    public function getContentCount(int $stateId): int
    {
        try {
            return $this->innerGateway->getContentCount($stateId);
        } catch (Throwable $e) {
            if ($e instanceof DBALException || $e instanceof PDOException) {
                throw DatabaseException::wrap($e);
            }

            throw $e;
        }
    }

    public function updateObjectStatePriority(int $stateId, int $priority): void
    {
        try {
            $this->innerGateway->updateObjectStatePriority($stateId, $priority);
        } catch (Throwable $e) {
            if ($e instanceof DBALException || $e instanceof PDOException) {
                throw DatabaseException::wrap($e);
            }

            throw $e;
        }
    }
}

class_alias(ExceptionConversion::class, 'eZ\Publish\Core\Persistence\Legacy\Content\ObjectState\Gateway\ExceptionConversion');
