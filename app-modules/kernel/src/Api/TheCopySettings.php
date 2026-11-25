<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * The core's two settings for a running backup or restore, by the names the stack keeps them under.
 */
enum TheCopySettings: string
{
    /** How fast a backup writes at most. */
    case BackupLimit = 'LEMONFIBER_BACKUP_LIMIT';

    /** How many minutes a copy may write nothing before it reads stalled. */
    case StalledAfter = 'LEMONFIBER_COPY_STALLED_MINUTES';

    /** Those of the two a listing holds, in the order it lists them. */
    public static function in(TheSettingsRead $read): TheSettingsRead
    {
        $found = [];

        foreach ($read as $setting) {
            if (self::tryFrom($setting->name()) instanceof self) {
                $found[] = $setting;
            }
        }

        return TheSettingsRead::these(...$found);
    }
}
