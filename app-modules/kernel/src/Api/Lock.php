<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use Closure;

/**
 * Whether the app is open, and the one way it becomes so.
 *
 * The app locks on backgrounding and requires biometric or passcode to resume.
 * One clause needs a type: biometric failure falls back to the
 * device passcode and **must not fall back to unlocked**.
 *
 * That is not a rule about what the code does — it is a rule about what the code
 * *can* do. So there is no `unlock()` taking a boolean, and no way to build an
 * open lock from a value a caller happened to have: {@see self::openedBy()}
 * takes an {@see Authenticated}, and the only thing that constructs one is a
 * platform prompt that actually succeeded.
 *
 * **The failure modes this shape refuses**, each of which is a plausible line:
 *
 * The open state **is** the proof rather than a flag beside it. A boolean can be
 * set by anything, and the thing that sets it wrongly is a prompt that came back
 * false — so there is no flag to set.
 *
 * - `$lock = Lock::open()` — no such constructor.
 * - `$lock->unlock($prompt->succeeded())` — a bool, and a bool from a cancelled
 *   prompt looks exactly like a bool from a successful one.
 * - `if (! $unlocked) { … } // else fall through` — the fall-through is where an
 *   app ends up open because nothing said to close it.
 *
 * A caller reads it by saying what happens in both cases, so there is no moment
 * at which "not locked" exists as a value that can be treated as "open".
 *
 *     $lock->either(
 *         held: fn (): Screen => $this->askForTheDevice(),
 *         open: fn (): Screen => $this->show(),
 *     );
 */
final readonly class Lock
{
    private function __construct(private ?Authenticated $by) {}

    /**
     * The state the app starts in and returns to.
     *
     * The device's own authentication is required on a cold start, so a
     * newly built app is held rather than open. That is the default because the
     * safe state should be the one you get by forgetting to decide.
     */
    public static function held(): self
    {
        return new self(null);
    }

    /**
     * Open, because the device said so.
     *
     * The parameter is the whole rule. `Authenticated` cannot be constructed
     * from a boolean, so there is no path from "the prompt returned something"
     * to "the app is open" that does not pass through a prompt that succeeded.
     */
    public static function openedBy(Authenticated $proof): self
    {
        return new self($proof);
    }

    /**
     * Say what happens either way, and get back what you built.
     *
     * No `isOpen()`. A boolean accessor is the check somebody forgets, and the
     * forgotten one here shows a stack's contents to whoever picked up the
     * phone.
     *
     * @template THeld of object
     * @template TOpen of object
     *
     * @param Closure(): THeld $held
     * @param Closure(): TOpen $open
     *
     * @return THeld|TOpen
     */
    public function either(Closure $held, Closure $open): object
    {
        // Read off the proof rather than off a flag, which is the same rule one
        // level down: a boolean can be set by anything, and the thing that sets
        // it wrongly is a prompt that came back false.
        return $this->by instanceof Authenticated ? $open() : $held();
    }
}
