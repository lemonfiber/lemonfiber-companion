<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use Closure;

/**
 * A setting's value, or the stack's own note that it is set and withheld.
 *
 * **The stack decides what is safe to show, and this carries that decision
 * rather than making it again.** The contract calls the row *one setting, as it
 * is safe to show*: a withheld value never leaves the machine, and what arrives
 * in its place is a note the stack wrote. So there is nothing here to redact,
 * and redacting again would be this app holding a second opinion about which
 * settings are sensitive — a copy of a judgement that belongs on the other
 * side.
 *
 * **Two arms rather than a string and a flag.** On the wire it is `value` and
 * `secret`, and a screen handed both has to remember which way round the flag
 * reads. Getting it backwards prints a withheld note where a value belongs,
 * which is merely wrong — or prints a value where the note belongs, which is
 * the failure that matters. A fold has no way to express the mistake.
 *
 * **No accessor, deliberately.** A `value()` beside an `isWithheld()` is a
 * check-then-get pair, and the check is the part a call site can forget. The
 * only way to the string is through {@see either()}, which cannot be entered
 * without saying what happens in both cases.
 */
final readonly class WhatASettingHolds
{
    private function __construct(private string $said, private bool $withheld) {}

    /**
     * The value, as the stack gave it.
     */
    public static function shown(string $value): self
    {
        return new self(said: $value, withheld: false);
    }

    /**
     * It is set, and the stack said so instead of saying what to.
     *
     * The note is the stack's wording and is shown as it stands. An app writing
     * its own — a row of dots, the word *hidden* — would be inventing a second
     * vocabulary for something the operator will also read in the stack's own
     * interfaces, and the two would drift.
     */
    public static function withheld(string $note): self
    {
        return new self(said: $note, withheld: true);
    }

    /**
     * Say what happens either way, and get back what you built.
     *
     * @template TShown of object
     * @template TWithheld of object
     *
     * @param  Closure(string): TShown  $shown
     * @param  Closure(string): TWithheld  $withheld
     * @return TShown|TWithheld
     */
    public function either(Closure $shown, Closure $withheld): object
    {
        // Read off the withholding rather than the showing: the arm with a
        // consequence is the one to branch on, and a fall-through is how the
        // branch nobody tested becomes the one that prints a secret.
        return $this->withheld ? $withheld($this->said) : $shown($this->said);
    }
}
