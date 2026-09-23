<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use Closure;

use function trim;

/**
 * What one service reaches, and on whose decision.
 *
 * Two arms, and the difference between them is who decided. **Asked** carries a
 * capability, the services that answer it and how that was settled: the
 * operator said what they wanted and the core worked out what provides it.
 * **By name** carries one service and the reason it was named: somebody gave an
 * instruction and the core carried it out.
 *
 * A plugin is forbidden to create the second, which is what makes the
 * distinction worth a type. Wiring by name is the operator's own act, and a
 * surface that rendered it as something the stack worked out would attribute a
 * decision to the wrong party — the failure {@see WhoSettledIt} prevents one
 * level down, arriving at the level above.
 *
 * **No accessor, for {@see WhatSettledIt}'s reason.** {@see whichever()} cannot
 * be entered without saying what happens in both cases, so a screen cannot draw
 * a by-name wiring as though it had a capability and claimants behind it.
 */
final readonly class HowItReaches
{
    private function __construct(
        private HowItWasReached $arm,
        private ?Capability $capability,
        private Services $services,
        private ?WhatSettledIt $settled,
        private ?ServiceId $named,
        // Absent as `null`, like every other field only one arm carries. A
        // blank reason is not the absent one: `byName()` refuses it, so `''` is
        // a value no reach of either arm holds — and a placeholder spelled that
        // way is indistinguishable from a reason nobody checked.
        private ?string $why,
    ) {}

    /**
     * A capability was asked for: this is the capability, what answers it, and
     * how that was settled.
     */
    public static function asked(Capability $capability, Services $services, WhatSettledIt $settled): self
    {
        return new self(
            arm: HowItWasReached::Asked,
            capability: $capability,
            services: $services,
            settled: $settled,
            named: null,
            why: null,
        );
    }

    /**
     * A service was named outright, and this is why.
     *
     * The reason is required and a blank one is refused. It is the only part of
     * a by-name wiring a later reader can evaluate, and without it the
     * instruction cannot be told from a choice the stack made.
     */
    public static function byName(ServiceId $service, string $why): self
    {
        $reason = trim($why);

        if ($reason === '') {
            throw AWiringExplainsNothing::whereAReasonWasExpected();
        }

        return new self(
            arm: HowItWasReached::ByName,
            capability: null,
            services: Services::none(),
            settled: null,
            named: $service,
            why: $reason,
        );
    }

    /**
     * Say what happens in both cases, and get back what you built.
     *
     * @template TAsked of object
     * @template TByName of object
     *
     * @param  Closure(Capability, Services, WhatSettledIt): TAsked  $asked  given the capability, what answers it, and how that was settled
     * @param  Closure(ServiceId, string): TByName  $byName  given the service named and the reason it was
     * @return TAsked|TByName
     */
    public function whichever(Closure $asked, Closure $byName): object
    {
        // Told apart by the word, which is `WhereASettingCameFrom`'s shape.
        // The by-name arm is read first deliberately: it is the one an operator
        // created by hand, and making it the fall-through is how it becomes the
        // case nobody tested — the argument `Reach::either()` makes about its
        // own blocked arm.
        if ($this->arm === HowItWasReached::ByName) {
            // A service by construction: `byName()` requires one and is the
            // only way to reach this arm.
            /** @var ServiceId $named */
            $named = $this->named;

            /** @var string $why */
            $why = $this->why;

            return $byName($named, $why);
        }

        // Both present by construction, for the same reason one step up.
        /** @var Capability $capability */
        $capability = $this->capability;

        /** @var WhatSettledIt $settled */
        $settled = $this->settled;

        return $asked($capability, $this->services, $settled);
    }
}
