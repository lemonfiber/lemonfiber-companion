<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\Screens;

use function count;

use Illuminate\View\View;

use function in_array;
use function is_string;

use Modules\Connection\Api\LetsGoOfARefusedSession;
use Modules\Kernel\Api\Concealed;
use Modules\Kernel\Api\HowManyLines;
use Modules\Kernel\Api\ItsContent;
use Modules\Kernel\Api\LocalZone;
use Modules\Kernel\Api\LookingFor;
use Modules\Kernel\Api\Saying;
use Modules\Kernel\Api\Scrollback;
use Modules\Kernel\Api\SecureStorage;
use Modules\Kernel\Api\ServiceId;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\TheAppsSettings;
use Modules\Kernel\Api\WhatItShowsDoes;
use Modules\Kernel\Api\Zone;
use Modules\Operator\Internal\OffersTheAppsSettings;
use Modules\Operator\Internal\Presenters\HowAScrollbackReads;
use Modules\Operator\Internal\ViewModels\WhatTheServiceTurnedOutToSay;
use Modules\Operator\Internal\WhatTheLogsAreOpenedWith;
use Modules\Wayfinding\Api\Screens\AsksTheStackAgain;
use Modules\Wayfinding\Api\TheWayAround;
use Modules\Wayfinding\Api\WhatItListensWith;
use Native\Mobile\Attributes\Lazy;
use Native\Mobile\Edge\NativeComponent;

use function view;

/**
 * What one of a stack's services has been saying.
 *
 * Four clauses: a read that is **bounded**, that is **searchable**,
 * that **names the service**, and that **states the view is a window rather
 * than the whole**. Three of them are held by {@see Scrollback}, where a screen
 * cannot drop them; this is where they reach somebody.
 *
 * **The search runs over the window and says so.** The endpoint takes a service
 * and a tail and no search term, so there is nothing to push down — what is
 * searched is what came back. The screen says that out loud rather than letting
 * an operator assume otherwise, because the assumption is the dangerous one: a
 * search that finds nothing reads as *the service never said it*, and what it
 * means is *not in the last two hundred lines*.
 *
 * **Narrowing does not re-ask.** Typing filters what is already held, which is
 * the cadence rule exactly — a screen that asked again per keystroke would open a
 * connection per letter to a machine on a home network, and would also change
 * what is being searched underneath the person searching it.
 *
 * **It reads one service, named in the route.** Not a picker over every service
 * the stack runs: this screen is reached from a finding that is already about
 * one, and a picker would need a second port and would put the choice before
 * the thing an operator came here to read.
 *
 * `Concealed` for the reason every stack-facing screen here is — and with more
 * force than most. A log line is whatever a service chose to print, which is the
 * one surface in this app where a secret could appear without anybody having
 * decided to put it there, so it is kept off the app switcher and
 * the report is assembled from what the operator chooses to send.
 */
#[Lazy]
#[Concealed]
#[ItsContent(WhatItShowsDoes::ChangesOnlyWhenAsked)]
final class WhatThisServiceSaid extends NativeComponent
{
    use AsksTheStackAgain;
    use OffersTheAppsSettings;
    use LetsGoOfARefusedSession;
    use FindsItsWayAround;

    /**
     * What somebody has typed into the search box.
     *
     * `public`, which is what `NativeComponent`'s property syncing needs to
     * reach: since 4.5.1 it writes only public, non-static properties, and a
     * screen whose state it cannot write silently stops holding what it thinks
     * it holds. It is also what fills the view's data, so the compiled
     * template finds the variable rather than an undefined one.
     *
     * **`render()` hands it to the view by name.** `native:model` expands to a
     * bare `$looking` in the compiled view, and the package fills the view's
     * data from a component's *public* properties — so a protected one arrives
     * undefined, which is a warning rather than a stop and draws an empty
     * field.
     */
    public string $looking = '';

    /** What came back, once the frame has asked. */
    public ?Scrollback $held = null;

    /**
     * The zone the phone's clock was set to when the lines were read.
     *
     * Asked once with the lines rather than on every read of them: a frame
     * reads the answer several times, and each asking is a call across the
     * bridge. Asking again reads it afresh.
     */
    public ?Zone $readIn = null;

    /**
     * The folds of decorative lines somebody has opened, by their place among the folds.
     *
     * @var list<int>
     */
    public array $unfolded = [];

    /** Whether somebody asked for the lines from the first error on. */
    public bool $fromTheFirstError = false;

    public function __construct(
        private readonly Saying $saying,
        private readonly SecureStorage $storage,
        protected readonly TheWayAround $around,
        private readonly LocalZone $here,
        protected readonly TheAppsSettings $settings,
        protected readonly WhatItListensWith $listening,
    ) {}

    /**
     * The service this screen is about.
     *
     * Read from the route for the same reason the stack is. A screen holding
     * the service it was opened with, on a frame whose URI names another, would
     * show one service's lines under another's heading — which is the failure
     * an operator acts on, because they would go and restart the wrong thing.
     */
    public function service(): ServiceId
    {
        $named = $this->param('service');

        return ServiceId::called(is_string($named) ? $named : '');
    }

    /**
     * The code a check reported about this service, where the road here carried one.
     *
     * Said above the lines rather than on the card that led here: a code is
     * for whoever helps, and somebody reading the logs to find out what
     * happened is where it is wanted.
     */
    public function reported(): string
    {
        return $this->carried(WhatTheLogsAreOpenedWith::Reported);
    }

    /** The exit code this service stopped with, where the road here carried one. */
    public function exited(): string
    {
        return $this->carried(WhatTheLogsAreOpenedWith::Exited);
    }

    /**
     * The service as the heading names it: what the stack calls it, where the
     * road here carried that, and otherwise the id the route names it by.
     */
    public function called(): string
    {
        $called = $this->carried(WhatTheLogsAreOpenedWith::Called);

        return $called !== '' ? $called : $this->service()->named();
    }

    /** What somebody has typed, for the box to hold it. */
    public function looking(): string
    {
        return $this->looking;
    }

    /** How many are shown, which is fewer than arrived while a search is on. */
    public function howMany(): int
    {
        return count($this->answer()->lines);
    }

    /**
     * Read the tail again.
     *
     * The action an obstacle must not take away. A control is not
     * hidden because the stack is unreachable — the app offers it and reports
     * the failure — and an obstacle screen with nothing on it does exactly what
     * the rule forbids: the only way back is leaving and returning, which
     * is named separately as the thing a screen must not rely on.
     *
     * It forgets the window as well as the fold, which the other screens have
     * no equivalent of. A held window is what lets this one search without
     * re-asking; keeping it through an *ask again* would hand back the same
     * lines and leave an operator tapping a button that changes nothing.
     */
    public function again(): void
    {
        $this->held = null;
        $this->readIn = null;
        $this->unfolded = [];
        $this->fromTheFirstError = false;
    }

    /**
     * Start the lines at the first one that declared an error.
     *
     * The platform offers no way to scroll a screen to a line, so this
     * leaves out what came before instead, and the screen opens at its top
     * rather than at its end. Nothing is asked again: it is the lines already
     * here, drawn from a later one.
     */
    public function showFromTheFirstError(): void
    {
        $this->fromTheFirstError = true;
    }

    /** Show every line again, from the top of the window. */
    public function showEveryLine(): void
    {
        $this->fromTheFirstError = false;
    }

    /**
     * Open a fold of decorative lines, or close it again.
     *
     * Held on the screen rather than asked for, because the lines are already
     * here: showing them is a change to what is drawn and not to what was read.
     */
    public function unfold(int $fold): void
    {
        $open = [];

        foreach ($this->unfolded as $already) {
            if ($already !== $fold) {
                $open[] = $already;
            }
        }

        $this->unfolded = in_array($fold, $this->unfolded, strict: true) ? $open : [...$open, $fold];
    }

    public function render(): View
    {
        return view('operator::what-this-service-said', ['looking' => $this->looking]);
    }

    /**
     * What came back, asked once per frame and narrowed on every read.
     *
     * The window is held and the narrowing is not, which is the whole of how
     * this screen searches without re-asking: typing changes what is shown and
     * never what was fetched, so the claim about the edge of the view survives
     * the search — and a machine on a home network is spoken to once.
     *
     * One accessor handing out the value rather than one per field, which is
     * {@see WhatThisStackRuns::answer()}'s shape and its argument: a method per
     * field is a method this class spends on saying nothing, and the next fact
     * the template needs then costs one it does not have. The template reads
     * the fields off what one asking produced, which is also the only thing
     * that could be true of them together.
     */
    public function answer(): WhatTheServiceTurnedOutToSay
    {
        $held = $this->held;

        if ($held instanceof Scrollback) {
            return new HowAScrollbackReads()->this(
                $held,
                $this->lookingFor(),
                $this->zone(),
                $this->unfolded,
                fromTheFirstError: $this->fromTheFirstError,
            );
        }

        return $this->ask();
    }

    /** What somebody typed, as the value that knows whether it is a search. */
    private function lookingFor(): LookingFor
    {
        return LookingFor::text($this->looking);
    }

    /**
     * Resume the session, read the tail, and flatten what came back.
     *
     * Split from {@see answer()} because the two are different questions — when
     * to ask, and what asking produced — and because `H8` counts the doors
     * either would otherwise have.
     */
    private function ask(): WhatTheServiceTurnedOutToSay
    {
        $stack = $this->stack();

        return $this->storage->resume($stack->id())->either(
            held: fn(Session $session): WhatTheServiceTurnedOutToSay => $this->read($stack, $session),
            notHeld: static fn(): WhatTheServiceTurnedOutToSay => new HowAScrollbackReads()->signedOut(),
        );
    }

    /** What the service said, or what the operator met instead. */
    private function read(Stack $stack, Session $session): WhatTheServiceTurnedOutToSay
    {
        $looking = $this->lookingFor();
        $zone = $this->zone();
        $unfolded = $this->unfolded;
        $fromTheFirstError = $this->fromTheFirstError;

        return $this->saying->saidBy(
            $stack,
            $session,
            $this->service(),
            HowManyLines::asMuchAsAPhoneShows(),
        )->either(
            this_: function (Scrollback $scrollback) use ($looking, $zone, $unfolded, $fromTheFirstError): WhatTheServiceTurnedOutToSay {
                $this->held = $scrollback;

                return new HowAScrollbackReads()->this($scrollback, $looking, $zone, $unfolded, fromTheFirstError: $fromTheFirstError);
            },
            met: $this->lettingGoIfRefused($stack, new HowAScrollbackReads()->met(...)),
        );
    }

    /** The zone the lines are read in, asked of the phone the first time. */
    private function zone(): Zone
    {
        return $this->readIn ??= $this->here->zone();
    }

    private function carried(WhatTheLogsAreOpenedWith $what): string
    {
        $carried = $this->data($what->value);

        return is_string($carried) ? $carried : '';
    }
}
