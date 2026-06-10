<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\Persistence\Legacy\AsyncPublication;

use Ibexa\Contracts\Core\Persistence\Content\AsyncPublication\AsyncPublicationJob;
use Ibexa\Contracts\Core\Persistence\Content\AsyncPublication\AsyncPublicationJobStatus;
use RuntimeException;

class Mapper
{
    /**
     * @param array<int, array<string, mixed>> $rows
     *
     * @return \Ibexa\Contracts\Core\Persistence\Content\AsyncPublication\AsyncPublicationJob[]
     */
    public function extractAsyncPublicationsFromRows(array $rows): array
    {
        return array_map($this->extractAsyncPublicationFromRow(...), $rows);
    }

    /**
     * @param array<string, mixed> $row
     */
    private function extractAsyncPublicationFromRow(array $row): AsyncPublicationJob
    {
        $asyncPublication = new AsyncPublicationJob();
        $asyncPublication->id = (int) $row['id'];
        $asyncPublication->contentId = (int) $row['content_id'];
        $asyncPublication->versionNo = (int) $row['version_no'];
        $asyncPublication->status = AsyncPublicationJobStatus::from((string) $row['status']);
        $asyncPublication->ownerId = (int) $row['owner_id'];
        $asyncPublication->created = (int) $row['created'];
        $asyncPublication->modified = (int) $row['modified'];
        $asyncPublication->errorMessage = $row['error'] !== null ? (string) $row['error'] : null;

        if ($row['data'] !== null) {
            $asyncPublication->data = json_decode((string) $row['data'], true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new RuntimeException('Error while decoding async publication data: ' . json_last_error_msg());
            }
        }

        return $asyncPublication;
    }
}
