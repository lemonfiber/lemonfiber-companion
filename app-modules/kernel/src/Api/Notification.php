<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function array_any;

use Closure;

/**
 * Something the core decided an operator should be told, about one stack.
 *
 * Four requirements meet in this one type, and they belong together because
 * each of them is about what a notification may **not** carry.
 *
 * **The app raises no alerts of its own.** Every notification originates in the
 * core's notification decisions, so this carries an identifier the core
 * declared and no text at all. There is nowhere to put a sentence the app
 * wrote. That is the difference between a rule and a habit: the words come from
 * the translator, keyed by the identifier.
 *
 * What it is filed under is the alert's name, which is never shown: it only
 * keys the notification, with its stack, so a resolution replaces its onset.
 *
 * The identifier is a {@see WhatTheCoreDecided} and not a {@see Code}, and that
 * is the other half of the same requirement. Inventing a code the core never
 * sends was never the work: this app already has codes — `Obstacle::code()`
 * mints them about a server that did not answer — so while the parameter was a
 * `Code`, raising an alert about the app's own trouble was a sentence anybody
 * could write, and the paragraph above said it could not be.
 *
 * **No credential, no household member's name, no requested title.**
 * All three are values, and none of them fits through an identifier. That is
 * the whole mechanism: a notification cannot leak what it has nowhere to hold.
 *
 * **Not shown for a stack no longer configured.** The `StackId` is
 * what lets that be asked at all. A notification arriving for a stack the
 * operator has removed is not exotic — it is what happens whenever a removal
 * and an in-flight alert cross — and without the id the only choices are
 * showing it anyway or dropping every pending alert on every removal.
 *
 * **While locked, no finding detail, no service name, no value read
 * from a stack.** {@see self::either()} is where that is kept, and it is kept
 * by *not passing* the stack to the guarded arm rather than by asking the
 * renderer to remember. A flag is read by whoever remembers to read it, and the
 * one who forgets is rendering to a lock screen.
 */
final readonly class Notification
{
    private function __construct(
        private StackId $about,
        private StackName $called,
        private WhatTheCoreDecided $says,
        private bool $guarded,
        private HowItIsWorded $worded,
        private AnAlertsName $filedUnder,
        private WhereATapLeads $leadsTo,
    ) {}

    /**
     * A notification the core decided on, for one stack.
     *
     * It takes no text. An adapter turning the core's decision into one of
     * these has that decision, a stack and the screen a tap opens, and nothing
     * else to hand over, which is that rule expressed as a signature.
     */
    public static function fromTheCore(Stack $about, WhatTheCoreDecided $says, WhereATapLeads $leadsTo): self
    {
        return new self($about->id(), $about->name(), $says, guarded: false, worded: HowItIsWorded::Onset, filedUnder: AnAlertsName::byItsKind($says), leadsTo: $leadsTo);
    }

    /**
     * A notification of one alert the core raised about one stack, filed under the alert's name.
     *
     * Its kind is what it says, and its name is what it is filed under, so the
     * resolution of a condition replaces the notification of its onset.
     */
    public static function ofAnAlert(Stack $about, AnAlert $alert, WhereATapLeads $leadsTo): self
    {
        return new self($about->id(), $about->name(), $alert->kind(), guarded: false, worded: HowItIsWorded::forAnAlert($alert->way()), filedUnder: $alert->filedUnder(), leadsTo: $leadsTo);
    }

    /**
     * The one notification its stack has more to tell than is shown, standing in for this one.
     *
     * Filed once per stack, so each that follows replaces it rather than
     * stacking, and leading where this one did.
     */
    public function beyondTheCap(): self
    {
        return new self($this->about, $this->called, $this->says, guarded: $this->guarded, worded: HowItIsWorded::MoreToTell, filedUnder: AnAlertsName::beyondTheCap(), leadsTo: $this->leadsTo);
    }

    /**
     * Whether this is still about a stack the device has.
     *
     * Asked here rather than by each caller comparing ids, so the comparison
     * deciding whether somebody is told about a machine they removed exists in
     * one place instead of once per notification surface.
     *
     * Variadic rather than an array: `array` says nothing about what is in it,
     * so a caller passing a list of `Stack` — or of strings — would be caught
     * by nothing until the loop compared a `StackId` against something that is
     * not one. The signature says `StackId` and the engine checks it.
     */
    public function concernsOneOf(StackId ...$configured): bool
    {
        return array_any($configured, fn(StackId $stack): bool => $this->about->is($stack));
    }

    /**
     * The same notification, in the form a locked device may show.
     *
     * Nothing is stripped, because there was never anything to strip — what
     * changes is which arm of {@see self::either()} runs, and the guarded arm
     * is not handed the stack.
     */
    public function whileLocked(): self
    {
        return new self($this->about, $this->called, $this->says, guarded: true, worded: $this->worded, filedUnder: $this->filedUnder, leadsTo: $this->leadsTo);
    }

    /** Which words it is shown in. */
    public function worded(): HowItIsWorded
    {
        return $this->worded;
    }

    /** The screen a tap on it opens. */
    public function leadsTo(): WhereATapLeads
    {
        return $this->leadsTo;
    }

    /**
     * The key it is filed as on the device: its stack and its alert's name, digested together.
     *
     * A later notification with the same key replaces this one, so only the
     * same alert from the same stack does: alert names are each stack's own,
     * and two stacks raising the same name never touch each other's.
     */
    public function filedAs(): string
    {
        return $this->filedUnder->filedFor($this->about);
    }

    /**
     * What this notification may say, decided by whether the device is locked.
     *
     * The two arms take different arguments, and that asymmetry is the rule:
     * `$plain` is given the name of the stack it is about, `$guarded` only the
     * code. A lock-screen renderer therefore has no stack to name, no service
     * to name and no reading to quote — not because it was told not to, but
     * because none of it arrived.
     *
     * @template TPlain of object
     * @template TGuarded of object
     *
     * @param  Closure(WhatTheCoreDecided, StackName): TPlain  $plain
     * @param  Closure(WhatTheCoreDecided): TGuarded  $guarded
     * @return TPlain|TGuarded
     */
    public function either(Closure $plain, Closure $guarded): object
    {
        return $this->guarded
            ? $guarded($this->says)
            : $plain($this->says, $this->called);
    }
}
