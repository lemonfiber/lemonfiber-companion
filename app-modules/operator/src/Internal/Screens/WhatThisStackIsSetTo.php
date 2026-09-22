<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\Screens;

use Closure;
use Illuminate\View\View;

use function is_string;

use Modules\Kernel\Api\Adjusting;
use Modules\Kernel\Api\Arranging;
use Modules\Kernel\Api\Concealed;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\SecureStorage;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Settings;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\Stacks;
use Modules\Kernel\Api\WhatTheStackMadeOfIt;
use Modules\Kernel\Api\WhatToSet;
use Modules\Kernel\Api\WhereTheChangeStands;
use Modules\Operator\Internal\LetsGoOfARefusedSession;
use Modules\Operator\Internal\Presenters\HowAChangeReads;
use Modules\Operator\Internal\Presenters\HowTheSettingsRead;
use Modules\Operator\Internal\ViewModels\WhatAChangeTurnedOutToBe;
use Modules\Operator\Internal\ViewModels\WhatThisStackIsSetToTurnedOutToBe;
use Modules\Operator\Internal\WhereAStackIs;
use Native\Mobile\Attributes\Lazy;
use Native\Mobile\Edge\NativeComponent;

use function view;

/**
 * Everything this stack is set to, as the stack itself lists it.
 *
 * **The screen holds no list of settings and this is the point of it.** What
 * is drawn is the listing that came back, whatever was in it. An app that knew
 * the settings it could show would show a subset the day the stack gained one,
 * would show it silently, and would leave the operator to conclude the setting
 * does not exist.
 *
 * So there is no allow-list here, no ordering, no grouping and no labels of
 * this app's own: a key and what it holds, in the order the stack said them.
 * Anything prettier would be this side deciding something the other side owns.
 *
 * **{@see Concealed} because a withheld row is still about a credential.** The
 * stack withholds the value and sends a note in its place, so nothing secret
 * reaches this screen — but the *names* do, and a task-switcher snapshot of a
 * list of credential keys is a list of what this household holds and where.
 */
#[Lazy]
#[Concealed]
final class WhatThisStackIsSetTo extends NativeComponent
{
    use LetsGoOfARefusedSession;

    /**
     * What the operator has typed, bound to the one input on this screen.
     *
     * `protected` rather than public, as {@see PairByTyping}'s is and for its
     * reason: `NativeComponent::__syncProperty()` assigns from the parent
     * class, which reaches a protected member of a subclass and not a private
     * one — exactly as open as the framework needs and no more. A mutable
     * public property is refused here, and this does not need to be one.
     *
     * That is why {@see render()} hands it to the view by name: the package
     * fills a view's data from a component's *public* properties, so a
     * protected one would arrive undefined and draw an empty field.
     *
     * Seeded from the value the stack showed when a setting is opened, so the
     * common edit — one character of a path — does not begin by retyping the
     * whole thing.
     */
    protected string $typed = '';

    protected ?WhatThisStackIsSetToTurnedOutToBe $answered = null;

    /** Which setting is open for changing, where one is. */
    protected ?string $changing = null;

    /** Where the proposal stands, once one has been put to the stack. */
    protected ?WhatAChangeTurnedOutToBe $proposed = null;

    public function __construct(
        private readonly Arranging $arranging,
        private readonly Adjusting $adjusting,
        private readonly SecureStorage $storage,
        private readonly Stacks $stacks,
    ) {}

    /** What the operator has typed into the one field. */
    public function typed(): string
    {
        return $this->typed;
    }

    public function stack(): Stack
    {
        $named = $this->param('stack');

        return $this->stacks->configured()->stack(
            StackId::rememberedAs(is_string($named) ? $named : ''),
        );
    }

    /**
     * Ask again.
     *
     * Dropping the held answer rather than re-reading here, so the next thing
     * that wants it does the asking — one path to the stack instead of two
     * that can disagree about what happened.
     */
    public function again(): void
    {
        $this->answered = null;
        $this->proposed = null;
    }

    /**
     * Which setting is open for changing, or nothing.
     *
     * A string rather than the row, because the template asks it per row and
     * a comparison against a key is the cheapest true thing to ask.
     */
    public function changing(): string
    {
        return $this->changing ?? '';
    }

    /**
     * Open a setting for changing, seeded with what it holds.
     *
     * **Refuses a key the listing did not offer a control for.** The template
     * draws the control only on a shown row, so a withheld key can only
     * arrive here from something other than the screen — and a screen that
     * trusted its own template would be one whose guarantee lives in a blade
     * file. Nothing offers to set a credential's value, and this is the half
     * of that which does not depend on markup.
     */
    public function change(string $key): void
    {
        foreach ($this->answer()->set as $setting) {
            if ($setting->key === $key && $setting->mayBeChanged) {
                $this->changing = $key;
                $this->typed = $setting->said;
                $this->proposed = null;

                return;
            }
        }

        $this->never();
    }

    /** Close the open setting without asking the stack anything. */
    public function never(): void
    {
        $this->changing = null;
        $this->typed = '';
        $this->proposed = null;
    }

    /**
     * Ask what setting it to what has been typed would come to.
     *
     * Nothing is written. What comes back is shown, and the agreeing is a
     * second tap — which is what makes the thing an operator agrees to the
     * thing they were shown.
     */
    public function wouldBe(string $key): void
    {
        $this->proposed = $this->put($key, static fn(
            Adjusting $adjusting,
            Stack $stack,
            Session $session,
            WhatToSet $asked,
        ): WhatTheStackMadeOfIt => $adjusting->wouldBe($stack, $session, $asked));
    }

    /**
     * Set it, having been shown what that would come to.
     *
     * The listing is dropped afterwards rather than patched: what the stack
     * now holds is the stack's to say, and a screen editing its own copy of
     * the row would be showing the operator what it believes rather than what
     * is there.
     */
    public function agree(string $key): void
    {
        $this->proposed = $this->put($key, static fn(
            Adjusting $adjusting,
            Stack $stack,
            Session $session,
            WhatToSet $asked,
        ): WhatTheStackMadeOfIt => $adjusting->agreedTo($stack, $session, $asked));
        $this->answered = null;
    }

    /** Where the proposal stands, or nothing where none has been put. */
    public function proposal(): ?WhatAChangeTurnedOutToBe
    {
        return $this->proposed;
    }

    /**
     * The proposal, where it is about this row and came back.
     *
     * Asked per row rather than held once and compared in the template,
     * because a control inside a listing acts on the row it is drawn beside —
     * a screen holding one proposal and a template that forgot to check whose
     * it was would show the review of one setting under another.
     */
    public function proposalFor(string $key): ?WhatAChangeTurnedOutToBe
    {
        $proposed = $this->proposed;

        if (!$proposed instanceof WhatAChangeTurnedOutToBe || $proposed->key !== $key || ! $proposed->went->cameBack()) {
            return null;
        }

        return $proposed;
    }

    public function goes(): WhereAStackIs
    {
        return WhereAStackIs::of($this->stack()->id());
    }

    public function render(): View
    {
        return view('operator::what-this-stack-is-set-to', ['typed' => $this->typed]);
    }

    public function answer(): WhatThisStackIsSetToTurnedOutToBe
    {
        return $this->answered ??= $this->ask();
    }

    /**
     * The one path to the stack that both asking and agreeing take.
     *
     * **The key is the caller's and is checked against the open one.** Every
     * control on this screen sits inside the listing, so each one names the
     * row it acts on — a tap that did not would be reaching for whichever
     * setting the screen happened to have open, which on a list is the wrong
     * row as often as the right one. Disagreeing with what is open means the
     * tap came from a row that is not the one being changed, and nothing is
     * put to the stack.
     *
     * @param Closure(Adjusting, Stack, Session, WhatToSet): WhatTheStackMadeOfIt $asking
     */
    private function put(string $key, Closure $asking): WhatAChangeTurnedOutToBe
    {
        if ($this->changing !== $key) {
            // Reached only by a tap on a control the template is not drawing.
            // Answered the way the screen would answer any other — nothing
            // happened — rather than by raising at somebody.
            //
            // One clause and not two. A blank key was tested for beside this
            // and could never be the one that decided: `$changing` is either
            // nothing or a key off the listing, so it is never blank, and a
            // blank key therefore fails the comparison first. A test for a
            // state that cannot occur is a line no case can reach, which is
            // the same thing as a line nobody has checked.
            return new HowAChangeReads()->nothingOpen();
        }

        $stack = $this->stack();
        $asked = WhatToSet::to($key, $this->typed);

        return $this->storage->resume($stack->id())->either(
            held: fn(Session $session): WhatAChangeTurnedOutToBe => $asking(
                $this->adjusting,
                $stack,
                $session,
                $asked,
            )->either(
                said: static fn(WhereTheChangeStands $stands): WhatAChangeTurnedOutToBe
                    => new HowAChangeReads()->stands($stands),
                refused: function (Obstacle $why) use ($stack): WhatAChangeTurnedOutToBe {
                    $this->letGoOfTheSession($why, $stack);

                    return new HowAChangeReads()->met($why);
                },
            ),
            notHeld: static fn(): WhatAChangeTurnedOutToBe => new HowAChangeReads()->signedOut(),
        );
    }

    private function ask(): WhatThisStackIsSetToTurnedOutToBe
    {
        $stack = $this->stack();

        return $this->storage->resume($stack->id())->either(
            held: fn(Session $session): WhatThisStackIsSetToTurnedOutToBe => $this->asked($stack, $session),
            notHeld: static fn(): WhatThisStackIsSetToTurnedOutToBe => new HowTheSettingsRead()->signedOut(),
        );
    }

    private function asked(Stack $stack, Session $session): WhatThisStackIsSetToTurnedOutToBe
    {
        return $this->arranging->asItStands($stack, $session)->either(
            told: static fn(Settings $set): WhatThisStackIsSetToTurnedOutToBe
                => new HowTheSettingsRead()->these($set),
            refused: function (Obstacle $why) use ($stack): WhatThisStackIsSetToTurnedOutToBe {
                $this->letGoOfTheSession($why, $stack);

                return new HowTheSettingsRead()->met($why);
            },
        );
    }
}
