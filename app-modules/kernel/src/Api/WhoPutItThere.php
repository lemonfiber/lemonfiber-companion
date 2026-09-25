<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use Closure;

use function is_string;
use function trim;

/**
 * Who put something there: the stack, the operator, a named plugin, a plugin
 * over what was there before, a plugin no longer installed, or nobody the
 * stack could establish.
 *
 * **One type for every surface that attributes, because the core publishes
 * one.** A setting's value, a check in a report and a service reaching
 * somewhere each arrive carrying the same `origin`, and the core keeps it as a
 * single vocabulary so that a surface gaining an attribution reads the same
 * words rather than inventing its own set. A type per
 * surface here would be three copies of one rule, and the copy that drifted
 * would be the one that learned to default.
 *
 * **One requirement is why this exists, and others are why it has more arms
 * than three.** Wherever a setting, a wiring or a check is shown its
 * origin is shown beside it, so that reading the thing and reading where it
 * came from are the same act. And an origin the stack could not determine is
 * reported as unknown — never as bundled, which is the wrong answer that looks
 * most like a right one. A default is the thing an operator is least likely to
 * question, so a wrong attribution to the stack is the one that survives
 * longest unchallenged. A value a plugin overrode says what it replaced, and a
 * value left by a plugin that is gone says so and names it.
 *
 * **An arm that names nothing holds nothing.** The arms that do — a plugin,
 * or the reason an origin could not be worked out — hold a string; the stack's
 * own and the operator's hold `null`, and not an empty string. An empty string here would be state
 * nothing can reach: the fold never hands it out on those arms, so no reader
 * could read it and no test could tell one value of it from another, which is
 * state nothing can hold right. `null` says there is nothing rather than
 * claiming there is a string and it is empty.
 *
 * **Arms rather than a word and optional fields.** On the wire it is a tagged
 * object: `{origin: 'bundled'}`, `{origin: 'operator'}`,
 * `{origin: 'plugin', named: …}`, `{origin: 'unknown', why: …}`,
 * `{origin: 'overridden', named: …, replaced: …}` and
 * `{origin: 'orphaned', named: …}`. Folded into a string plus nullables, every call site would have to remember which arm
 * carries which — and the one that forgets prints an empty plugin name or
 * swallows the reason an origin is unknown. Here a name cannot be read without
 * being in the arm that has one.
 *
 * **No accessor, for {@see WhatASettingHolds}'s reason.** A `named()` beside an
 * `isFromAPlugin()` is a check-then-get pair, and the check is the half a call
 * site can skip. {@see whichever()} cannot be entered without saying what
 * happens in every case, so an arm added here is a compile error at every
 * reader rather than a screen that silently draws nothing.
 */
final readonly class WhoPutItThere
{
    private function __construct(
        private WhoSetIt $arm,
        private ?string $said,
        private ?WhatItReplaced $replaced = null,
    ) {}

    /**
     * The stack's own default, which nobody has changed.
     */
    public static function bundled(): self
    {
        return new self(arm: WhoSetIt::Bundled, said: null);
    }

    /**
     * Somebody put it there, through this app or through the stack's own
     * interfaces.
     */
    public static function operator(): self
    {
        return new self(arm: WhoSetIt::Operator, said: null);
    }

    /**
     * A plugin put it there, and this is the plugin.
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
            throw AnOriginIsUnnamed::fromAPlugin();
        }

        return new self(arm: WhoSetIt::Plugin, said: $plugin);
    }

    /**
     * A plugin set it over what was there before, and this is what it replaced.
     *
     * The name is required for {@see plugin()}'s reason.
     */
    public static function overridden(string $named, WhatItReplaced $replaced): self
    {
        $plugin = trim($named);

        if ($plugin === '') {
            throw AnOriginIsUnnamed::fromAPlugin();
        }

        return new self(arm: WhoSetIt::Overridden, said: $plugin, replaced: $replaced);
    }

    /**
     * A plugin set it and is no longer installed, and the value is still in force.
     *
     * The name is required for {@see plugin()}'s reason: a value left behind
     * by a plugin nobody can name is one nobody can go and look up.
     */
    public static function orphaned(string $named): self
    {
        $plugin = trim($named);

        if ($plugin === '') {
            throw AnOriginIsUnnamed::fromAPlugin();
        }

        return new self(arm: WhoSetIt::Orphaned, said: $plugin);
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
            throw AnOriginIsUnnamed::unknownForNoStatedReason();
        }

        return new self(arm: WhoSetIt::Unknown, said: $reason);
    }

    /**
     * Say what happens in every case, and get back what you built.
     *
     * @template TBundled of object
     * @template TOperator of object
     * @template TPlugin of object
     * @template TUnknown of object
     * @template TOverridden of object
     * @template TOrphaned of object
     *
     * @param  Closure(): TBundled  $bundled
     * @param  Closure(): TOperator  $operator
     * @param  Closure(string): TPlugin  $plugin  given the plugin's name
     * @param  Closure(string): TUnknown  $unknown  given the stack's reason
     * @param  Closure(string, WhatItReplaced): TOverridden  $overridden  given the plugin's name and what it replaced
     * @param  Closure(string): TOrphaned  $orphaned  given the name of the plugin that is gone
     * @return TBundled|TOperator|TPlugin|TUnknown|TOverridden|TOrphaned
     */
    public function whichever(
        Closure $bundled,
        Closure $operator,
        Closure $plugin,
        Closure $unknown,
        Closure $overridden,
        Closure $orphaned,
    ): object {
        // Read off what is held before which arm holds it, because that is
        // the question with more than one answer: an arm that names nothing
        // holds nothing, and the arm that replaced something holds that too.
        // Every branch is reached by one of the cases above.
        $said = $this->said;

        if (! is_string($said)) {
            return $this->arm === WhoSetIt::Operator ? $operator() : $bundled();
        }

        return $this->naming($said, $plugin, $unknown, $overridden, $orphaned);
    }

    /**
     * The arms that name somebody, or say why nobody could be named.
     *
     * @template TPlugin of object
     * @template TUnknown of object
     * @template TOverridden of object
     * @template TOrphaned of object
     *
     * @param  Closure(string): TPlugin  $plugin
     * @param  Closure(string): TUnknown  $unknown
     * @param  Closure(string, WhatItReplaced): TOverridden  $overridden
     * @param  Closure(string): TOrphaned  $orphaned
     * @return TPlugin|TUnknown|TOverridden|TOrphaned
     */
    private function naming(string $said, Closure $plugin, Closure $unknown, Closure $overridden, Closure $orphaned): object
    {
        $replaced = $this->replaced;

        if ($replaced instanceof WhatItReplaced) {
            return $overridden($said, $replaced);
        }

        if ($this->arm === WhoSetIt::Orphaned) {
            return $orphaned($said);
        }

        return $this->arm === WhoSetIt::Plugin ? $plugin($said) : $unknown($said);
    }
}
