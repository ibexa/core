<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\Core\Repository\Events\Setting;

use Ibexa\Contracts\Core\Repository\Event\AfterEvent;
use Ibexa\Contracts\Core\Repository\Values\Setting\Setting;
use Ibexa\Contracts\Core\Repository\Values\Setting\SettingUpdateStruct;

final class UpdateSettingEvent extends AfterEvent
{
    private Setting $updatedSetting;

    private Setting $setting;

    private SettingUpdateStruct $settingUpdateStruct;

    public function __construct(
        Setting $updatedSetting,
        Setting $setting,
        SettingUpdateStruct $settingUpdateStruct
    ) {
        $this->updatedSetting = $updatedSetting;
        $this->setting = $setting;
        $this->settingUpdateStruct = $settingUpdateStruct;
    }

    public function getUpdatedSetting(): Setting
    {
        return $this->updatedSetting;
    }

    public function getSetting(): Setting
    {
        return $this->setting;
    }

    public function getSettingUpdateStruct(): SettingUpdateStruct
    {
        return $this->settingUpdateStruct;
    }
}
