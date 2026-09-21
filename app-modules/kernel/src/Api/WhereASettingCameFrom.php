<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use Closure;

use function trim;

/**
 * Who put a setting's value there: the stack, the operator, a named plugin, or
 * nobody this stack could establish.
 *
 * **One requirement is why this exists and another is why it has four arms
 * rather than three.** Wherever a setting is shown its origin is shown beside
 * it, so that reading the value and reading where it came from are the same
 * act. And an origin the stack could not determine is reported as unknown —
 * never as bundled, which is the wrong answer that looks most like a right
 * one. A default is the thing an operator is least likely to question, so a
 * wrong attribution to the stack is the one that survives longest
 * unchallenged.
 *
 * **Four arms rather than a word and two optional strings.** On the wire it is
 * a tagged object: `{origin: 'bundled'}`, `{origin: 'operator'}`,
 * `{origin: 'plugin', named: …}`, `{origin: 'unknown', why: …}`. Folded into a
 * string plus two nullables, every call site would have to remember which arm
 * carries which — and the one that forgets prints an empty plugin name or
 * swallows the reason an origin is unknown. Here a name cannot be read without
 * being in the arm that has one.
 *
 * **No accessor, for {@see WhatASettingHolds}'s reason.** A `named()` beside an
 * `isFromAPlugin()` is a check-then-get pair, and the check is the half a call
 * site can skip. {@see whichever()} cannot be entered without saying what
 * happens in all four cases, so an arm added here is a compile error at every
 * reader rather than a screen that silently draws nothing.
 */
final readonly class WhereASettingCameFrom
{
    private function __construct(private WhoSetIt $arm, private string $said) {}

    /**
     * The stack's own default, which nobody has changed.
     */
    public static function bundled(): self
    {
        return new self(arm: WhoSetIt::Bundled, said: '');
    }

    /**
     * Somebody set it, through this app or through the stack's own interfaces.
     */
    public static function operator(): self
    {
        return new self(arm: WhoSetIt::Operator, said: '');
    }

    /**
     * A plugin set it, and this is the plugin.
     *
     * The name is trimmed and then required, in that order, for the reason
     * {@see Setting::called()} requires a key: an attribution to a plugin
     * nobody can name is not an attribution. It reads on the screen as *from
     * the ▒ plugin*, which is worse than saying the origin is unknown — it
     * asserts a provenance and then withholds the only part of it that
     * matters.
     */
    public static function plugin(string $named): self
    {
        $plugin = trim($named);

        if ($plugin === '') {
            throw ASettingsOriginIsUnnamed::fromAPlugin();
        }

        return new self(arm: WhoSetIt::Plugin, said: $plugin);
    }

    /**
     * The stack could not establish it, and this is what it said about that.
     *
     * The reason is the stack's wording and is carried through as it
     * stands, for {@see WhatASettingHolds::withheld()}'s reason: an app writing
     * its own would be a second vocabulary for something the operator also
     * reads in the stack's own interfaces.
     *
     * A blank reason is refused rather than shown. *Unknown* with nothing after
     * it invites the reading this arm exists to prevent — that it is probably a
     * default — and the stack has said something here in every case the
     * contract permits.
     */
    public static function unknown(string $why): self
    {
        $reason = trim($why);

        if ($reason === '') {
            throw ASettingsOriginIsUnnamed::unknownForNoStatedReason();
        }

        return new self(arm: WhoSetIt::Unknown, said: $reason);
    }

    /**
     * Say what happens in all four cases, and get back what you built.
     *
     * @template TBundled of object
     * @template TOperator of object
     * @template TPlugin of object
     * @template TUnknown of object
     *
     * @param  Closure(): TBundled  $bundled
     * @param  Closure(): TOperator  $operator
     * @param  Closure(string): TPlugin  $plugin  given the plugin's name
     * @param  Closure(string): TUnknown  $unknown  given the stack's reason
     * @return TBundled|TOperator|TPlugin|TUnknown
     */
    public function whichever(
        Closure $bundled,
        Closure $operator,
        Closure $plugin,
        Closure $unknown,
    ): object {
        // `match` on the closed set rather than a chain of ifs on strings: a
        // case added to {@see WhoSetIt} without a branch here is an unhandled
        // match error at the first reading, which is the loudest this can be.
        return match ($this->arm) {
            WhoSetIt::Bundled => $bundled(),
            WhoSetIt::Operator => $operator(),
            WhoSetIt::Plugin => $plugin($this->said),
            WhoSetIt::Unknown => $unknown($this->said),
        };
    }
}
