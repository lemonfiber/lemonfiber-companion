<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\Screens;

use Illuminate\View\View;

use function is_string;

use Modules\Connection\Api\HowTheSignInWent;
use Modules\Kernel\Api\Admitted;
use Modules\Kernel\Api\Admitting;
use Modules\Kernel\Api\Concealed;
use Modules\Kernel\Api\Credential;
use Modules\Kernel\Api\Instant;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\SecureStorage;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\Stacks;
use Modules\Kernel\Api\Whose;
use Modules\Kernel\Api\WhySessionCannotBeKept;
use Modules\Operator\Internal\WhereAStackIs;
use Native\Mobile\Attributes\Lazy;
use Native\Mobile\Edge\NativeComponent;

use function trim;
use function view;

/**
 * Offering a stack the operator's password, once, in exchange for a session.
 *
 * The exchange in front of somebody: the password is offered **once** and never
 * retained for re-sending, which is why this screen holds what was typed and
 * hands it to {@see Credential::of()} at the moment of the tap rather than
 * keeping a credential across frames. A `Credential` empties itself when it is
 * offered, so the one this screen makes cannot outlive the attempt it was made
 * for.
 *
 * **Which stack is a route parameter, not a choice made here.** A device keeps
 * each stack's session separate, and a screen that picked its own stack would
 * be the place two stacks come to share one. The operator chose on
 * {@see YourStacks}; this screen is told.
 *
 * **A session that could not be kept is not a sign-in**, and the screen says
 * so. The stack opened one, this device has nowhere to put it, and the next
 * launch will ask for the password again — an operator told they are in and
 * asked again a minute later was misled by an app that knew at the time. This
 * is {@see HowThePairingWent}'s lesson one boundary further in.
 *
 * **What it does not do is retry.** An obstacle distinguishes a refused password
 * from a door that has stopped listening, and {@see HowTheSignInWent} carries
 * that distinction to the template so the *try again* control appears for the
 * one case where trying again is the remedy. Where the door is counting
 * attempts, another one extends the wait — a screen that offered the button
 * anyway would be actively unhelpful.
 *
 * **`#[Concealed]` because a password is on the glass.** The capture rule names
 * credentials by name, and this is the one screen in the application where an
 * operator types one — precisely the frame the task switcher keeps and a screen
 * recording captures.
 *
 * **`#[Lazy]` because everything it is handed is a port.** Signing in reaches a
 * stack over the network and a keychain across a process boundary, which is
 * `F4`'s argument for not doing either while a frame is trying to appear.
 */
#[Lazy]
#[Concealed]
final class SignIntoAStack extends NativeComponent
{
    /**
     * The password, as it stands in the field.
     *
     * `protected` rather than public, matching {@see PairByTyping}:
     * `NativeComponent::__syncProperty()` assigns from the parent class, which
     * reaches a protected member of a subclass and does not reach a private
     * one.
     *
     * **`render()` hands it to the view by name.** `native:model` expands to a
     * bare `$typed` in the compiled view, and the package fills the view's
     * data from a component's *public* properties — so a protected one arrives
     * undefined, which is a warning rather than a stop and draws an empty
     * field.
     *
     * **A bare string rather than a {@see Credential}.** A field holds
     * characters; a credential is what they become, once, at the moment they
     * are offered. Holding a `Credential` here would mean holding one across
     * frames — and one that has been spent cannot be offered again, so the
     * second attempt after a wrong password would fail for a reason having
     * nothing to do with the password.
     */
    protected string $typed = '';

    /** What became of the attempt, once one has been made. */
    protected HowTheSignInWent $went = HowTheSignInWent::NotYet;

    public function __construct(
        private readonly Admitting $admitting,
        private readonly SecureStorage $storage,
        private readonly Stacks $stacks,
    ) {}

    /** What the operator has typed into the password field. */
    public function typed(): string
    {
        return $this->typed;
    }

    /** What became of the attempt, once one has been made. */
    public function went(): HowTheSignInWent
    {
        return $this->went;
    }

    /**
     * The stack this screen is signing into.
     *
     * Read from the route on every frame rather than held, so there is one
     * answer to *which machine* and it is the one the URI names. A held stack
     * is how a screen comes to be showing one machine's name while offering
     * another machine the password.
     *
     * Raises {@see StackIsNotConfigured} where the device holds no such stack,
     * which is a route naming a stack that has been forgotten — a launch-time
     * fault rather than a screen state, and the same treatment
     * {@see Configured::stack()} gives it.
     *
     * A route parameter arrives as `mixed`, because the navigation stack's own
     * parameter array is untyped. Anything that is not a string becomes the
     * empty one, which {@see StackId::rememberedAs()} refuses by name — the
     * same refusal a route naming no stack at all gets, which is what it is.
     */
    public function stack(): Stack
    {
        $named = $this->param('stack');

        return $this->stacks->configured()->stack(
            StackId::rememberedAs(is_string($named) ? $named : ''),
        );
    }

    /** Whether the operator is in, which is when the way onwards is offered. */
    public function isSignedIn(): bool
    {
        return $this->went->isSignedIn();
    }

    /** Whether the password field belongs on the screen in this state. */
    public function mayTry(): bool
    {
        return $this->went->mayTry();
    }

    /** Whether the way back to asking belongs on it instead. */
    public function mayStartOver(): bool
    {
        return $this->went->mayStartOver();
    }

    /**
     * Put the screen back to asking, after the operator has gone and acted.
     *
     * The other half of a `Guided` standing: the remedy was instructions, they
     * followed them, and the screen is still holding what it was told before
     * they did. Forgetting rather than retrying — nothing is offered to the
     * stack here, because the password was spent when it was offered and this
     * screen does not hold one to offer again.
     */
    public function startOver(): void
    {
        $this->went = HowTheSignInWent::NotYet;
    }

    /**
     * Whether the control that offers the password may be tapped at all.
     *
     * An empty field is not an attempt. Offering it would spend a try against a
     * door that counts them, on a password nobody typed.
     */
    public function mayOffer(): bool
    {
        return trim($this->typed) !== '';
    }

    /**
     * Offer what was typed, and say what happened.
     *
     * The whole of the exchange in one method: the string becomes a credential here
     * and nowhere else, the credential is spent by being offered, and what
     * comes back is either a session this device keeps or a reason it did not.
     *
     * The field is cleared whatever happened, which is the half worth stating.
     * A password left in a field after a successful sign-in is a password on
     * the glass for as long as the screen is up; after a refusal it is a
     * password the next tap would offer again unchanged, which is a second
     * attempt the operator did not decide to make.
     */
    public function offer(): void
    {
        $said = Credential::of($this->typed);
        $this->typed = '';

        $this->went = $this->admitting->admit($this->stack(), $said)->either(
            opened: fn(Session $session, Instant $until, Whose $whose): HowTheSignInWent
                => $this->kept($session, $whose),
            refused: static fn(Obstacle $why): HowTheSignInWent => HowTheSignInWent::met($why),
        );
    }

    /**
     * Where an operator who has just signed in goes next.
     *
     * The report for the stack they signed into — which is what they came for.
     * Until this existed the screen said *"you can reach it from the main
     * screen"* and left them to go and do it, which is an app telling somebody
     * to navigate on its behalf.
     *
     * Built from the stack this screen is already about, so it cannot lead to
     * another machine's report, and spelled once here rather than in the
     * template the way {@see YourStacks::signInAt()} is.
     */
    public function onwardsTo(): string
    {
        return WhereAStackIs::of($this->stack()->id())->health();
    }

    /** The frame, by name. */
    public function render(): View
    {
        return view('operator::sign-into-a-stack', ['typed' => $this->typed]);
    }

    /**
     * Write the session down, saying whether that happened.
     *
     * Named rather than inlined into the closure above because the analyser
     * refuses a checked exception raised inside one, and because a closure
     * reaching a second port is a closure nobody reads twice.
     *
     * **The expiry the stack sent is deliberately not taken.** `Admitted` carries it
     * and nothing on this screen acts on when a session ends: a session that has
     * expired is the obstacle screen, which does not exist yet, and a value written
     * down for a screen nobody has built is a value nothing holds to being right. It
     * is there through {@see Admitted} for whoever builds that screen.
     *
     * **Whose it is is taken**, and is kept with the session rather than worked out
     * again later. The stack said who signed in; asking a second time would be a
     * second answer able to disagree with the first, and the first is the one the
     * store goes on holding.
     */
    private function kept(Session $session, Whose $whose): HowTheSignInWent
    {
        return $this->storage->keep($this->stack()->id(), $session, $whose)->either(
            kept: static fn(): HowTheSignInWent => HowTheSignInWent::SignedIn,
            refused: static fn(WhySessionCannotBeKept $why): HowTheSignInWent => HowTheSignInWent::unkept($why),
        );
    }
}
