<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function trim;

/**
 * The three things the core says when a check did not pass.
 *
 * Together rather than as three fields on {@see WhatTheCheckSaid}, because they
 * arrive together and are absent together. The version that kept them
 * separately had to give the passing arm a blank meaning to hold the shape —
 * a value nothing reads, which no test can tell from any other value, and which
 * a reader has to work out is deliberate.
 *
 * Held here, the passing arm carries nothing at all and there is no placeholder
 * to explain.
 */
final readonly class WentWrong
{
    private function __construct(
        private Code $code,
        private string $meaning,
        private Remedies $remedies,
        private Severity $severity,
        private Standing $standing,
        private WhatItSaysUnderneath $underneath,
    ) {}

    /**
     * The meaning is refused when blank, for the reason {@see Finding} refuses a
     * blank title: a red row with no sentence is one the operator cannot act on
     * and cannot search for, and a core producing one has a fault worth seeing
     * where the payload is read rather than on somebody's screen.
     */
    public static function of(
        Code $code,
        string $meaning,
        Remedies $remedies,
        Severity $severity,
        Standing $standing,
        WhatItSaysUnderneath $underneath,
    ): self {
        $said = trim($meaning);

        if ($said === '') {
            throw CheckSaidNothing::under($code);
        }

        return new self($code, $said, $remedies, $severity, $standing, $underneath);
    }

    /**
     * How much this matters, in the engine's own judgement.
     *
     * Taken as sent rather than derived from the verdict, for the reason
     * {@see Overall} gives about a run's own word: a screen working it out from
     * what it can see would be a second opinion about a judgement the engine
     * already made, and the two would disagree the first time a check was added
     * that only one of them knew how to weigh.
     */
    public function severity(): Severity
    {
        return $this->severity;
    }

    /**
     * Where it stands with respect to being fixed.
     *
     * The distinction {@see Standing} exists for is `Actionable` against
     * `Guided` — one puts a button on a screen and the other puts instructions
     * on it. A screen that guessed would offer to do something it cannot do.
     *
     * `Remediable` is the case this makes reachable and nothing yet reads: it
     * means lemonfiber can fix the thing itself, which is what a repair
     * flow is about offering.
     */
    public function standing(): Standing
    {
        return $this->standing;
    }

    /**
     * What the core said underneath, where it said anything.
     *
     * The detail is available and does not lead, so it is asked for by
     * name rather than arriving beside the meaning — a screen has to reach for
     * it, which is what *must not lead* means on a surface with one column.
     */
    public function underneath(): WhatItSaysUnderneath
    {
        return $this->underneath;
    }

    /** The code an operator can search for, and quote to somebody. */
    public function code(): Code
    {
        return $this->code;
    }

    /** What it means, in the core's words rather than the app's. */
    public function meaning(): string
    {
        return $this->meaning;
    }

    /** What the core offered to do about it. */
    public function remedies(): Remedies
    {
        return $this->remedies;
    }
}
