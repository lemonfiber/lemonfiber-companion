<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function sprintf;

/**
 * Where a proposed change stands once it has been read against what is in force.
 *
 * Four words, and the distance between two of them is the whole reason this is
 * not a boolean: `unchanged` and `applied` both mean the setting now holds what
 * was asked for, and only one of them wrote anything. An operator told *applied*
 * about a setting that already held the value would go looking for a restart
 * that never happened.
 */
enum Stance: string
{
    /**
     * The setting already holds what was asked for.
     *
     * Nothing to apply, nothing to confirm, and the file is not touched — a
     * write of the same value would move its timestamp and read afterwards as
     * an edit somebody made.
     */
    case Unchanged = 'unchanged';

    /**
     * Staged and not applied: nothing has been written.
     *
     * Either it is consequential and nobody has said yes yet, or it was
     * rehearsed.
     */
    case Pending = 'pending';

    /** Something stood in the way, and the review says what. */
    case Blocked = 'blocked';

    /** Written. */
    case Applied = 'applied';

    public function saidOnTheScreen(): string
    {
        return sprintf('config.stance.%s', $this->value);
    }

    /**
     * Whether the setting now holds what was asked for.
     *
     * True of two stances and for different reasons, which is exactly why a
     * screen should ask this rather than compare — the question *did this
     * work* has one answer and the question *did anything happen* has another.
     */
    public function holdsWhatWasAsked(): bool
    {
        return match ($this) {
            self::Unchanged, self::Applied => true,
            self::Pending, self::Blocked => false,
        };
    }

    /**
     * Whether anything was written.
     *
     * Beside {@see holdsWhatWasAsked()} rather than folded into it, because the
     * pair is the distinction: `unchanged` is true of the first and false of
     * this one, and a screen with only one of the two questions cannot tell an
     * operator whether to expect a restart.
     */
    public function wroteSomething(): bool
    {
        return match ($this) {
            self::Applied => true,
            self::Unchanged, self::Pending, self::Blocked => false,
        };
    }
}
