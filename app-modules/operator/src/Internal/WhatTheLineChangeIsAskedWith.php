<?php

declare(strict_types=1);

namespace Modules\Operator\Internal;

use function array_any;

use Closure;
use Modules\Kernel\Api\ALift;
use Modules\Kernel\Api\ALineChange;

use function trim;

/**
 * What the operator typed, as a change to the line to ask for, or why it is not one.
 *
 * The one place the fields become a request. Every limit, the hours, the line
 * and the cap are carried as typed, for the core to read; a lift is a whole
 * number of minutes. A change asking nothing, or a lift that is not a number,
 * is said on the screen rather than sent.
 */
final readonly class WhatTheLineChangeIsAskedWith
{
    private function __construct(private ALineChange|string $answer) {}

    /** The fields as typed, the lift as whole minutes or blank for none. */
    public static function from(string $down, string $up, string $active, string $line, string $cap, string $exceeded, string $lift): self
    {
        $minutes = trim($lift);

        if ($minutes !== '' && ! WrittenAsAWholeNumber::is($minutes)) {
            return new self('stacks.line_change.lift.is_minutes');
        }

        $lifted = $minutes === '' ? ALift::none() : ALift::forMinutes((int) $minutes);

        if ($lifted->isNone() && ! self::anyWritten($down, $up, $active, $line, $cap, $exceeded)) {
            return new self('stacks.line_change.nothing_asked');
        }

        return new self(ALineChange::asked($lifted, $down, $up, $active, $line, $cap, $exceeded));
    }

    /**
     * Say what happens in each case, and get back what you built.
     *
     * @template TAsked of object
     * @template TNot of object
     *
     * @param Closure(ALineChange): TAsked $asked
     * @param Closure(string): TNot        $notAskable given the catalogue key saying why
     *
     * @return TAsked|TNot
     */
    public function either(Closure $asked, Closure $notAskable): object
    {
        return $this->answer instanceof ALineChange ? $asked($this->answer) : $notAskable($this->answer);
    }

    /** Whether any of these fields has something in it. */
    private static function anyWritten(string ...$fields): bool
    {
        return array_any($fields, fn(string $field): bool => trim($field) !== '');
    }
}
