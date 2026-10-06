<?php

declare(strict_types=1);

namespace Modules\Wayfinding\Api;

/**
 * Whose words App settings speaks in, which the screen it is opened from says.
 *
 * App settings belongs to nobody's session and is the same screen for
 * everybody. Opened from a member's Profile it calls a stack *the house* and
 * a reading what the app saved, because nothing a member is shown names how
 * the machine works; opened from anywhere else it speaks as it always has.
 * Only the words differ, never what the settings do.
 */
enum WhoTheSettingsSpeakTo: string
{
    case Anyone = 'anyone';
    case AMember = 'a_member';

    /** The heading over how long things are kept. */
    public function kept(): string
    {
        return match ($this) {
            self::Anyone => 'settings.readings',
            self::AMember => 'settings.household.readings',
        };
    }

    /** The row saying how long things are kept for. */
    public function keepFor(): string
    {
        return match ($this) {
            self::Anyone => 'settings.keep_readings',
            self::AMember => 'settings.household.keep_readings',
        };
    }

    /** The note under how long things are kept. */
    public function keptExplained(): string
    {
        return match ($this) {
            self::Anyone => 'settings.keep_readings_is',
            self::AMember => 'settings.household.keep_readings_is',
        };
    }

    /** The heading over the order the stacks are listed in. */
    public function theStacks(): string
    {
        return match ($this) {
            self::Anyone => 'settings.stacks',
            self::AMember => 'settings.household.stacks',
        };
    }

    /** The order the stacks are listed in. */
    public function theirOrder(): string
    {
        return match ($this) {
            self::Anyone => 'settings.stack_order',
            self::AMember => 'settings.household.stack_order',
        };
    }

    /** The question asked before clearing what the phone saved. */
    public function clearing(): string
    {
        return match ($this) {
            self::Anyone => 'settings.clear_confirm',
            self::AMember => 'settings.household.clear_confirm',
        };
    }
}
