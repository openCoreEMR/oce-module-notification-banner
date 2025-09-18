<?php

/**
 * Manages the configuration options for the OpenCoreEMR Notification Banner.
 *
 * @package   OpenCoreEMR
 * @link      http://www.open-emr.org
 * @author    Michael A. Smith <michael@opencoreemr.com>
 * @copyright Copyright (c) 2025 OpenCoreEMR Inc
 * @license   GNU General Public License 3
 */

namespace OpenCoreEMR\Modules\NotificationBanner;

use OpenEMR\Services\Globals\GlobalSetting;

class GlobalConfig
{
    public const CONFIG_OPTION_ACTIVE = 'oce_notification_banner_active';
    public const CONFIG_OPTION_MESSAGE = 'oce_notification_banner_message';

    public string $message {
        get {
            return $GLOBALS[self::CONFIG_OPTION_MESSAGE] ?? '';
        }
    }

    public bool $isActive {
        get {
            return (bool)($GLOBALS[self::CONFIG_OPTION_ACTIVE] ?? false);
        }
    }

    /**
     * Returns true if all of the settings have been configured.  Otherwise it returns false.
     *
     * @return bool
     */
    public function isConfigured()
    {
        return true;
    }

    public function getGlobalSettingSectionConfiguration()
    {
        $settings = [
            self::CONFIG_OPTION_ACTIVE => [
                'title' => 'Activate Notification Banner',
                'description' => 'Toggle the banner display',
                'type' => GlobalSetting::DATA_TYPE_BOOL,
                'default' => false
            ],
            self::CONFIG_OPTION_MESSAGE => [
                'title' => 'Notification Banner Message',
                'description' => 'The message to display when the banner is active',
                'type' => GlobalSetting::DATA_TYPE_TEXT,
                'default' => ''
            ]
        ];
        return $settings;
    }
}
