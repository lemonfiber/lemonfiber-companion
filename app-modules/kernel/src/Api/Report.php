<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * What a diagnostic run found, and what it amounts to.
 *
 * The two travel together because they answer different questions and a screen
 * needs both. `overall` is what goes at the top — one word, the engine's own
 * judgement — and the findings are what goes under it. Handing back only the
 * findings would leave every caller to work the word out again, which is the
 * derivation `Overall` exists to prevent.
 *
 * **Empty findings with `Healthy` is the ordinary case**, not an absent report.
 * A run where nothing had anything to say is the outcome the product is for,
 * and `Findings::none()` says it without a null (C2).
 *
 * **The pairing is not checked here.** A `Broken` report with no findings, or a
 * `Healthy` one with a failure in it, would be the engine contradicting itself —
 * and this side inventing a rule about which half to believe would be a second
 * opinion about somebody else's data, wrong in a way nobody could see from the
 * screen. If it ever happens it is a bug where the judgement is made, and it
 * should look like one there.
 */
final readonly class Report
{
    private function __construct(private Overall $overall, private Findings $findings) {}

    public static function of(Overall $overall, Findings $findings): self
    {
        return new self($overall, $findings);
    }

    /** What the run amounts to, in the engine's own word. */
    public function overall(): Overall
    {
        return $this->overall;
    }

    /** Each finding, in the order the checks produced them. */
    public function findings(): Findings
    {
        return $this->findings;
    }
}
