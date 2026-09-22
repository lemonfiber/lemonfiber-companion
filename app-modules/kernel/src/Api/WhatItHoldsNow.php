<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use Closure;

/**
 * What a setting holds before a proposed change, where it holds anything.
 *
 * **Two arms because the wire has two cases and they are opposite to read.**
 * `from` is absent where the setting holds nothing yet and carries a string
 * otherwise, and those are *there is nothing here* and *there is something
 * here* — a screen showing an empty line for the first is telling somebody a
 * setting is blank when it may be that nobody asked.
 *
 * **What this deliberately cannot say is whether the string was withheld.**
 * `SettingReport` carries a `secret` flag and `ConfigChange` carries no
 * equivalent, while its own field descriptions say withholding happens in both
 * places. So a note saying *set, not shown* and a real value arrive here as the
 * same string and there is nothing to tell them apart. Showing either verbatim
 * is safe — the stack withheld before sending — but this surface cannot mark
 * the withheld one the way the listing beside it does. Raised as
 * lemonfiber/spec#459 rather than guessed at: a surface inferring *this looks
 * like a redaction* from the text would be reading English to decide whether
 * something is a credential.
 */
final readonly class WhatItHoldsNow
{
    private function __construct(private ?string $said) {}

    /**
     * It holds this, as the stack chose to show it.
     */
    public static function shown(string $value): self
    {
        return new self($value);
    }

    /**
     * Nothing yet — the setting has never been given a value.
     *
     * Kept apart from an empty string, which is a value somebody chose.
     */
    public static function nothingYet(): self
    {
        return new self(null);
    }

    /**
     * Say what happens either way, and get back what you built.
     *
     * @template TShown of object
     * @template TNothing of object
     *
     * @param  Closure(string): TShown  $shown
     * @param  Closure(): TNothing  $nothingYet
     * @return TShown|TNothing
     */
    public function either(Closure $shown, Closure $nothingYet): object
    {
        // Read off the absence, as the rest of these do: the arm with
        // something to explain is the one the type is written around.
        return $this->said === null ? $nothingYet() : $shown($this->said);
    }
}
