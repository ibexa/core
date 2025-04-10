<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\Persistence\Legacy\Setting;

/**
 * @internal For internal use by Persistence Handlers.
 */
abstract class Gateway
{
    public const string SETTING_SEQ = 'ibexa_setting_id_seq';
    public const string SETTING_TABLE = 'ibexa_setting';

    abstract public function insertSetting(
        string $group,
        string $identifier,
        string $serializedValue
    ): int;

    abstract public function updateSetting(
        string $group,
        string $identifier,
        string $serializedValue
    ): void;

    /**
     * @return array<string, mixed>|null
     */
    abstract public function loadSetting(
        string $group,
        string $identifier
    ): ?array;

    /**
     * @return array<string, mixed>|null
     */
    abstract public function loadSettingById(
        int $id
    ): ?array;

    abstract public function deleteSetting(
        string $group,
        string $identifier
    ): void;
}
