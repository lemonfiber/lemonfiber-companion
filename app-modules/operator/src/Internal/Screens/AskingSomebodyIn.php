<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\Screens;

use Closure;
use Illuminate\Contracts\Translation\Translator;
use Illuminate\View\View;

use function is_string;

use Modules\Kernel\Api\AnInvitation;
use Modules\Kernel\Api\AnInvitationAgreed;
use Modules\Kernel\Api\AnInvitationAskedFor;
use Modules\Kernel\Api\AnInvitationToPassOn;
use Modules\Kernel\Api\Concealed;
use Modules\Kernel\Api\Encoding;
use Modules\Kernel\Api\HowOften;
use Modules\Kernel\Api\Inviting;
use Modules\Kernel\Api\Job;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\SecureStorage;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Sharing;
use Modules\Kernel\Api\SomebodyInTheHousehold;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\Stacks;
use Modules\Kernel\Api\TheMembers;
use Modules\Kernel\Api\WhatBecameOfTheInvitation;
use Modules\Kernel\Api\WhatBecomesOfUnrated;
use Modules\Operator\Internal\LetsGoOfARefusedSession;
use Modules\Operator\Internal\Presenters\HowTheInvitationReads;
use Modules\Operator\Internal\Presenters\HowWhoIsInReads;
use Modules\Operator\Internal\ViewModels\TheInvitationTurnedOutToBe;
use Modules\Operator\Internal\ViewModels\WhoIsInTurnedOutToBe;
use Modules\Operator\Internal\WhatTheInvitationIsAskedWith;
use Modules\Operator\Internal\WhatTheSheetSaid;
use Modules\Operator\Internal\WhereAStackIs;
use Native\Mobile\Attributes\Lazy;
use Native\Mobile\Attributes\Poll;
use Native\Mobile\Edge\NativeComponent;

use function view;

/**
 * Asking somebody in: who is in already, what an invitation would grant, sending it, and handing it over.
 *
 * **It opens on who is in.** The one reading a frame asks for is everybody the
 * media server holds an account for, joined or still invited, so an operator
 * asking somebody in sees first whether they are in already, and a password is
 * taken off by picking the person rather than by spelling them.
 *
 * **Nothing is made until the operator has seen what it would make.** The
 * first tap asks the stack to rehearse the invitation, and what comes back —
 * the libraries, the limit, unrated material, whether they may ask, how long
 * it stands, and what would be taken back on the way past — is drawn before
 * anything else is offered. The yes is a second tap, and it sends the request
 * the rehearsal was asked with rather than whatever is in the fields now.
 *
 * **Every answer is work the stack names and this follows**, so the handle is
 * held and asked after at {@see HowOften::WhileWorkRuns} while it runs, the way
 * {@see HowCurrentThisStackIs} follows an update.
 *
 * **The address is handed over as it came**: as text, as a code another phone
 * scans off this one's screen, and through the device's own sharing with the
 * stack's caution beside it. Nothing here sends anything to anybody.
 *
 * **A password is taken off by naming the person and nothing else**, and only a
 * person the reading listed. Nothing on this screen shows, sets or carries one.
 *
 * `Concealed` for the reason every stack-facing screen here is, and because an
 * invitation's address is a way in to somebody's household.
 */
#[Lazy]
#[Concealed]
final class AskingSomebodyIn extends NativeComponent
{
    use LetsGoOfARefusedSession;

    /**
     * The name they will sign in as, bound to its field.
     *
     * Public for {@see WhatThisStackIsSetTo::$typed}'s reason, which is also
     * why the template reaches it: the package fills a view from a screen's
     * public properties.
     */
    public string $name = '';

    /** The libraries they may open, separated by commas, bound to its field; empty is every one. */
    public string $libraries = '';

    /** The age above which things are held back, bound to its field; empty is no limit. */
    public string $age = '';

    /** What becomes of unrated material, as one of {@see WhatBecomesOfUnrated}'s words, or empty to leave it to the stack. */
    public string $unrated = '';

    /** Whose password the operator has asked to take off and not yet said yes to, or empty. */
    public string $member = '';

    /** What the last invitation was asked with, which is what a yes sends. */
    public ?AnInvitationAskedFor $asked = null;

    /** The invitation the stack last answered, rehearsed or carried out. */
    public ?AnInvitation $invitation = null;

    /** The handle of the work being followed, while there is one. */
    public ?string $following = null;

    /** The name the work being followed is about, which a refusal is drawn with. */
    public string $about = '';

    /** The catalogue key for what handing it over came to, or empty where it has not been. */
    public string $passedOn = '';

    /** Where it has got to, once this frame has asked. Public for {@see WhatIsRunningHere::$answered}'s reason. */
    public ?TheInvitationTurnedOutToBe $going = null;

    /** Who is in, once this frame has asked. Public for the same reason. */
    public ?WhoIsInTurnedOutToBe $answered = null;

    public function __construct(
        private readonly Inviting $inviting,
        private readonly Encoding $encoding,
        private readonly Sharing $sharing,
        private readonly SecureStorage $storage,
        private readonly Stacks $stacks,
        private readonly Translator $catalogue,
    ) {}

    /** The stack this screen is about, read from the route on every frame. */
    public function stack(): Stack
    {
        $named = $this->param('stack');

        return $this->stacks->configured()->stack(
            StackId::rememberedAs(is_string($named) ? $named : ''),
        );
    }

    /**
     * Ask again, which an obstacle must not take away.
     *
     * Asks after the work being followed where there is some; otherwise it
     * puts the fields back in front of the operator, who asks by tapping. A
     * request that never reached the stack is not sent again on its own.
     */
    public function again(): void
    {
        $this->going = null;
        $this->answered = null;
    }

    /** Leave the answer and start over, keeping what was typed. */
    public function startAgain(): void
    {
        $this->asked = null;
        $this->invitation = null;
        $this->following = null;
        $this->passedOn = '';
        $this->member = '';
        $this->going = null;
    }

    /** Say what becomes of unrated material: `held-back`, `let-through`, or anything else to leave it to the stack. */
    public function unratedIs(string $word): void
    {
        $this->unrated = WhatBecomesOfUnrated::tryFrom($word)->value ?? '';
    }

    /** The catalogue key for what becomes of unrated material as chosen here, or for leaving it to the stack. */
    public function unratedSaid(): string
    {
        return WhatBecomesOfUnrated::tryFrom($this->unrated)?->saidOnTheScreen() ?? 'stacks.invitation.unrated.left_to_the_stack';
    }

    /**
     * Ask the stack what inviting them would come to, making nothing.
     *
     * What was typed becomes the request here, and a name left blank or an
     * age that is not a number is said on the screen rather than sent.
     */
    public function offer(): void
    {
        $this->startAgain();

        $this->going = WhatTheInvitationIsAskedWith::from($this->name, $this->libraries, $this->age, $this->unrated)->either(
            asked: fn(AnInvitationAskedFor $asked): TheInvitationTurnedOutToBe => $this->put(
                $asked->name(),
                function (Stack $stack, Session $session) use ($asked): WhatBecameOfTheInvitation {
                    $this->asked = $asked;

                    return $this->inviting->wouldInvite($stack, $session, $asked);
                },
            ),
            notAskable: static fn(string $why): TheInvitationTurnedOutToBe => new HowTheInvitationReads()->notAskable($why),
        );
    }

    /**
     * Invite them, as the rehearsal on the screen described.
     *
     * Reached only beneath a rehearsal that leaves something to hand over; a
     * tap from anywhere else does nothing, rather than sending a request
     * nobody was shown.
     */
    public function send(): void
    {
        $asked = $this->asked;
        $offered = $this->invitation;

        if (! $asked instanceof AnInvitationAskedFor || ! $offered instanceof AnInvitation || ! $offered->wasRehearsed() || ! $offered->standing()->leavesSomethingToHandOver()) {
            return;
        }

        $agreed = AnInvitationAgreed::after($asked, $offered);
        $this->invitation = null;

        $this->going = $this->put(
            $asked->name(),
            fn(Stack $stack, Session $session): WhatBecameOfTheInvitation => $this->inviting->invite($stack, $session, $agreed),
        );
    }

    /**
     * Ask to take this member's password off, which is said before it is done.
     *
     * **Refuses a name the reading did not list**, for
     * {@see WhatThisStackIsSetTo::change()}'s reason: the control is drawn only
     * beside a listed member, and a screen whose guarantee lived in its
     * template would take a password off whoever a tap named.
     */
    public function wouldTakeThePasswordOff(string $name): void
    {
        foreach ($this->answer()->members as $member) {
            if ($member->name === $name) {
                $this->member = $name;

                return;
            }
        }

        $this->neverMind();
    }

    /** Leave their password where it is. */
    public function neverMind(): void
    {
        $this->member = '';
    }

    /** Take the named member's password off, so they choose the next one at the media server. */
    public function takeThePasswordOff(): void
    {
        if ($this->member === '') {
            return;
        }

        $who = SomebodyInTheHousehold::called($this->member);
        $this->startAgain();

        $this->going = $this->put(
            $who->name(),
            fn(Stack $stack, Session $session): WhatBecameOfTheInvitation => $this->inviting->takeThePasswordOff($stack, $session, $who),
        );
    }

    /**
     * Hand the invitation over through the device's own sharing.
     *
     * Only an invitation the stack carried out, with an address to hand over,
     * is offered; the sheet is the platform's and where it goes is the
     * operator's choice.
     */
    public function passOn(): void
    {
        $invitation = $this->invitation;

        if (! $invitation instanceof AnInvitation || $invitation->wasRehearsed() || ! $invitation->standing()->leavesSomethingToHandOver()) {
            return;
        }

        $toHand = $invitation->toHand();

        $this->passedOn = $this->sharing->passOn(AnInvitationToPassOn::of($toHand, $this->catalogue->choice('stacks.invitation.covering', $toHand->hours(), [
            'name' => $toHand->name(),
            'stack' => $this->stack()->name()->shown(),
        ])))->either(
            over: static fn(): WhatTheSheetSaid => new WhatTheSheetSaid('stacks.invitation.passed_on'),
            // One sentence for both refusals: whichever it was, nothing was
            // sent and the address is on the screen to hand over another way.
            refused: static fn(): WhatTheSheetSaid => new WhatTheSheetSaid('stacks.invitation.not_passed_on'),
        )->said;
    }

    /**
     * Ask after the work again while the stack is carrying it out.
     *
     * Nothing happens unless it is running, so a finished answer is not asked
     * for again. The interval is {@see HowOften}'s, which {@see cadence()}
     * states on the screen.
     */
    #[Poll(HowOften::WHILE_WORK_RUNS_MS)]
    public function whileItRuns(): void
    {
        if ($this->howItIsGoing()->isWorking) {
            $this->going = null;
        }
    }

    /** How often this screen asks after work running, as the screen states it. */
    public function cadence(): HowOften
    {
        return HowOften::WhileWorkRuns;
    }

    /** Where this machine's screens are. */
    public function goes(): WhereAStackIs
    {
        return WhereAStackIs::of($this->stack()->id());
    }

    public function render(): View
    {
        return view('operator::asking-somebody-in');
    }

    /** Who is in, asked once per frame. */
    public function answer(): WhoIsInTurnedOutToBe
    {
        $stack = $this->stack();

        return $this->answered ??= $this->storage->resume($stack->id())->either(
            held: fn(Session $session): WhoIsInTurnedOutToBe => $this->inviting->whoIsIn($stack, $session)->either(
                found: static fn(TheMembers $members): WhoIsInTurnedOutToBe => new HowWhoIsInReads()->these($members),
                met: function (Obstacle $why) use ($stack): WhoIsInTurnedOutToBe {
                    $this->letGoOfTheSession($why, $stack);

                    return new HowWhoIsInReads()->met($why);
                },
            ),
            notHeld: static fn(): WhoIsInTurnedOutToBe => new HowWhoIsInReads()->signedOut(),
        );
    }

    /**
     * Where asking has got to, asked once per frame.
     *
     * Asks the stack after the work being followed where there is some, and
     * otherwise says nothing has been asked.
     */
    public function howItIsGoing(): TheInvitationTurnedOutToBe
    {
        $following = $this->following;

        return $this->going ??= $following === null
            ? new HowTheInvitationReads()->notAsked()
            : $this->put(
                $this->about,
                fn(Stack $stack, Session $session): WhatBecameOfTheInvitation => $this->inviting->whatBecameOf($stack, $session, Job::named($following)),
            );
    }

    /**
     * One act put to the stack, with the session this device holds for it.
     *
     * @param Closure(Stack, Session): WhatBecameOfTheInvitation $asking
     */
    private function put(string $name, Closure $asking): TheInvitationTurnedOutToBe
    {
        $stack = $this->stack();
        $this->about = $name;

        return $this->storage->resume($stack->id())->either(
            held: fn(Session $session): TheInvitationTurnedOutToBe => $this->shown($asking($stack, $session), $stack),
            notHeld: static fn(): TheInvitationTurnedOutToBe => new HowTheInvitationReads()->signedOut($name),
        );
    }

    /** What the stack said, as the screen draws it, holding what to follow or to agree to. */
    private function shown(WhatBecameOfTheInvitation $became, Stack $stack): TheInvitationTurnedOutToBe
    {
        $name = $this->about;

        return $became->either(
            underway: function (Job $job) use ($name): TheInvitationTurnedOutToBe {
                $this->following = $job->shown();

                return new HowTheInvitationReads()->running($name);
            },
            answered: function (AnInvitation $invitation): TheInvitationTurnedOutToBe {
                $this->following = null;
                $this->invitation = $invitation;

                // Who is in is the stack's to say again once it has made or
                // changed an account, rather than this screen's to patch.
                if (! $invitation->wasRehearsed()) {
                    $this->answered = null;
                }

                return new HowTheInvitationReads()->answered($invitation, $this->encoding->codeFor($invitation->toHand()->address()));
            },
            ended: function () use ($name): TheInvitationTurnedOutToBe {
                $this->following = null;

                return new HowTheInvitationReads()->ended($name);
            },
            refused: function (string $because) use ($name): TheInvitationTurnedOutToBe {
                $this->following = null;

                return new HowTheInvitationReads()->refused($because, $name);
            },
            met: function (Obstacle $why) use ($stack, $name): TheInvitationTurnedOutToBe {
                $this->letGoOfTheSession($why, $stack);

                return new HowTheInvitationReads()->met($why, $name);
            },
        );
    }
}
