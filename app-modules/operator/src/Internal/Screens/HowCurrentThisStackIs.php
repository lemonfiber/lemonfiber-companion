<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\Screens;

use function count;

use Illuminate\View\View;

use function is_string;

use Modules\Kernel\Api\Concealed;
use Modules\Kernel\Api\KeepingCurrent;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\SecureStorage;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\Stacks;
use Modules\Kernel\Api\TakingAnUpdate;
use Modules\Kernel\Api\Underway;
use Modules\Kernel\Api\Upkeep;
use Modules\Operator\Internal\LetsGoOfARefusedSession;
use Modules\Operator\Internal\Presenters\HowUpkeepReads;
use Modules\Operator\Internal\ViewModels\WhatOneReleaseSays;
use Modules\Operator\Internal\ViewModels\WhatTheUpkeepTurnedOutToBe;
use Modules\Operator\Internal\WhereAStackIs;
use Native\Mobile\Attributes\Lazy;
use Native\Mobile\Edge\NativeComponent;

use function view;

/**
 * Where this machine stands on being up to date.
 *
 * The up-to-date screen. It opens on the answer — current, an update waiting, or
 * not looked at recently — because that is the decision an operator holding a
 * phone is making. They are not comparing version strings; they are deciding
 * whether tonight is the night.
 *
 * **It does not compare versions, and that is the requirement.** Which of the
 * three states the stack is in is the stack's answer, read and shown. An app
 * that worked it out from two strings would be wrong about a withdrawn
 * release, about a patch series, and about a stack whose channel the operator
 * changed — and wrong silently, because nothing on either side would compare
 * its opinion to the stack's.
 *
 * **A withdrawn release is left out of what is offered and said about what is
 * running.** Those are opposite errands: offering one is refused, and a
 * stack that is *on* one is something an operator has to be told. Dropping it
 * from both would leave them reading a screen that says nothing is wrong.
 *
 * `Concealed` for the reason every stack-facing screen here is: what a house
 * runs is the household's business, and a diagnostic report is
 * assembled from what the operator chooses to send rather than from what a
 * screen happened to hold.
 */
#[Lazy]
#[Concealed]
final class HowCurrentThisStackIs extends NativeComponent
{
    use LetsGoOfARefusedSession;

    /**
     * What came back, once the frame has asked.
     *
     * `public`, which is what `NativeComponent`'s property syncing needs to
     * reach: since 4.5.1 it writes only public, non-static properties, and a
     * screen whose state it cannot write silently stops holding what it thinks
     * it holds. It is also what fills the view's data, so the compiled
     * template finds the variable rather than an undefined one.
     */
    public ?WhatTheUpkeepTurnedOutToBe $answered = null;

    /**
     * The update being asked about, while the operator decides.
     *
     * `public` for the reason above, and held rather than passed through the
     * template because what is confirmed has to be the thing that was shown —
     * a release re-read after the yes is an update to whatever the stack had by
     * then, agreed against a screen that is no longer true.
     */
    public ?TakingAnUpdate $asking = null;

    public function __construct(
        private readonly KeepingCurrent $keeping,
        private readonly SecureStorage $storage,
        private readonly Stacks $stacks,
    ) {}

    /**
     * The stack this screen is about.
     *
     * Read from the route on every frame rather than held, so there is one
     * answer to *which machine* and it is the one the URI names.
     */
    public function stack(): Stack
    {
        $named = $this->param('stack');

        return $this->stacks->configured()->stack(
            StackId::rememberedAs(is_string($named) ? $named : ''),
        );
    }

    /** How many releases are offered, which is what the empty state asks. */
    public function howMany(): int
    {
        return count($this->answer()->waiting);
    }

    /**
     * Ask the stack again.
     *
     * The action an obstacle must not take away. Forgetting what came back
     * rather than re-reading here, so the next accessor asks — which keeps this
     * one act and keeps the reading rule true: one asking per frame, and a frame that
     * starts when somebody taps.
     */
    public function again(): void
    {
        $this->answered = null;
    }

    /**
     * Offer to take a release, and ask first.
     *
     * The version arrives as a string because a template can hand over nothing
     * else, and is matched against what this screen actually read — so a
     * release this screen never showed cannot be agreed to, whatever a template
     * sends. That is the argument {@see WhatThisStackRuns::wouldYouLike()}
     * makes about a service name, and it is the same one.
     *
     * Every update asks. There is no unconfirmed arm here the way there is for
     * a start, because there is no update that takes nothing away: it stops
     * services, and which ones is the whole of what the confirmation says.
     */
    public function wouldYouLike(string $version): void
    {
        $taking = $this->agreementFor($version);

        if (! $taking instanceof TakingAnUpdate) {
            return;
        }

        $this->asking = $taking;
    }

    /** Take the update that was agreed to. */
    public function agree(): void
    {
        $taking = $this->asking;

        if (! $taking instanceof TakingAnUpdate) {
            return;
        }

        $this->asking = null;

        $this->send($taking);
    }

    /** Leave it. */
    public function neverMind(): void
    {
        $this->asking = null;
    }

    /**
     * What is being asked about, where anything is.
     *
     * The template draws the confirmation off this rather than off a flag, so
     * the thing named in the question is the thing that will be sent.
     */
    public function asking(): ?TakingAnUpdate
    {
        return $this->asking;
    }

    /** How many services the pending question would change. */
    public function wouldChange(): int
    {
        return $this->asking instanceof TakingAnUpdate ? $this->asking->changing()->count() : 0;
    }

    /** How many of those the stack said nothing will put back. */
    public function cannotBePutBack(): int
    {
        return $this->asking instanceof TakingAnUpdate ? $this->asking->cannotBePutBack()->count() : 0;
    }

    /** Where this machine's screens are. */
    public function goes(): WhereAStackIs
    {
        return WhereAStackIs::of($this->stack()->id());
    }

    public function render(): View
    {
        return view('operator::how-current-this-stack-is');
    }

    /**
     * What came back, asked once per frame.
     *
     * One accessor handing out the value rather than one per field: a method
     * per field is a method this class spends on saying nothing, and the next
     * fact the template needs then costs one it does not have.
     */
    public function answer(): WhatTheUpkeepTurnedOutToBe
    {
        return $this->answered ??= $this->ask();
    }

    /**
     * The confirmation for a version, where this screen actually showed it.
     *
     * Matched against what was read rather than trusted: a release this screen
     * never offered cannot be agreed to, whatever a template sends — and the
     * services travel from the same reading, so the confirmation names what
     * was on the screen rather than what the stack has by the time somebody
     * taps.
     */
    private function agreementFor(string $version): ?TakingAnUpdate
    {
        $row = $this->row($version);

        if (! $row instanceof WhatOneReleaseSays) {
            return null;
        }

        return TakingAnUpdate::agreed(
            $row->release(),
            $this->answer()->changing,
            $this->answer()->cannotBePutBack,
        );
    }

    /** The release this screen showed under that version, where it showed one. */
    private function row(string $version): ?WhatOneReleaseSays
    {
        foreach ($this->answer()->waiting as $release) {
            if ($release->version === $version) {
                return $release;
            }
        }

        return null;
    }

    /**
     * Send what was agreed to, and forget what was read.
     *
     * Forgetting rather than re-reading here, so the next accessor asks — the
     * listing after an update is taken is a different listing, and a screen
     * that kept the old one would show an evening that has already happened.
     */
    private function send(TakingAnUpdate $taking): void
    {
        $stack = $this->stack();

        $this->storage->resume($stack->id())->either(
            held: function (Session $session) use ($stack, $taking): Underway {
                $underway = $this->keeping->take($stack, $session, $taking);
                $this->answered = null;

                return $underway;
            },
            notHeld: static fn(): Underway => Underway::met(Obstacle::CredentialWasRefused),
        );
    }

    /**
     * Resume the session, ask the stack, and flatten what came back.
     *
     * Split from {@see answer()} because the two are different questions — when
     * to ask, and what asking produced — and because `H8` counts the doors
     * either would otherwise have.
     */
    private function ask(): WhatTheUpkeepTurnedOutToBe
    {
        $stack = $this->stack();

        return $this->storage->resume($stack->id())->either(
            held: fn(Session $session): WhatTheUpkeepTurnedOutToBe => $this->asked($stack, $session),
            notHeld: static fn(): WhatTheUpkeepTurnedOutToBe => new HowUpkeepReads()->signedOut(),
        );
    }

    /** What the stack said, or what the operator met instead. */
    private function asked(Stack $stack, Session $session): WhatTheUpkeepTurnedOutToBe
    {
        return $this->keeping->standing($stack, $session)->either(
            stands: static fn(Upkeep $upkeep): WhatTheUpkeepTurnedOutToBe
                => new HowUpkeepReads()->standing($upkeep),
            met: function (Obstacle $why) use ($stack): WhatTheUpkeepTurnedOutToBe {
                $this->letGoOfTheSession($why, $stack);

                return new HowUpkeepReads()->met($why);
            },
        );
    }
}
