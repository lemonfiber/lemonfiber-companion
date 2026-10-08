<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\Screens;

use function array_filter;
use function array_values;
use function in_array;

use Modules\Kernel\Api\AGuardAskedFor;
use Modules\Kernel\Api\Concealed;
use Modules\Kernel\Api\Form;
use Modules\Kernel\Api\Forms;
use Modules\Kernel\Api\Guarding;
use Modules\Kernel\Api\HowOftenAScreenLooks;
use Modules\Kernel\Api\HowTheGuardIsGoing;
use Modules\Kernel\Api\ItsContent;
use Modules\Kernel\Api\Job;
use Modules\Kernel\Api\SecureStorage;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\Supervising;
use Modules\Kernel\Api\TheAppsSettings;
use Modules\Kernel\Api\WhatItShowsDoes;
use Modules\Kernel\Api\WhatTheGuardSaw;
use Modules\Operator\Internal\AsksWhatFormsItHas;
use Modules\Operator\Internal\OffersTheAppsSettings;
use Modules\Operator\Internal\Presenters\HowAGuardReads;
use Modules\Operator\Internal\ViewModels\HowTheGuardWent;
use Modules\Operator\Internal\ViewModels\TheFormsAsFound;
use Modules\Operator\Internal\ViewModels\WhatTheGuardWouldGuard;
use Modules\Wayfinding\Api\Screens\AsksTheStackAgain;
use Modules\Wayfinding\Api\Screens\DrawsItsTemplate;
use Modules\Wayfinding\Api\TheWayAround;
use Modules\Wayfinding\Api\WhatItListensWith;
use Native\Mobile\Attributes\Lazy;
use Native\Mobile\Attributes\Poll;
use Native\Mobile\Edge\NativeComponent;

/**
 * A guard on this machine's data location, for the forms the operator names,
 * held only while this screen keeps asking about it.
 *
 * **The guard lives as long as the screen does.** Started over the web API it
 * is work with no ending of its own, which the stack keeps only while somebody
 * asks about it. This screen asks on a declared cadence while it is open, and
 * lets the guard go when it is left. It says so before the guard starts and
 * for as long as it runs, and it never presents the guard as hosted: one that
 * outlives the screen is handed to the machine on {@see WhatKeepsRunningHere},
 * which this screen points to.
 *
 * **Starting is an act of its own.** Naming forms asks nothing; the guard is
 * sent only from {@see agree()}, after a question naming the forms it would
 * stop.
 *
 * **A guard that ended says how.** It saw the data location go, with the
 * forms it named, whether stopping them worked, and why it ended; it never
 * started, with the stack's reason; it was let go without seeing anything; or
 * the stack no longer knows it. Each is drawn in words of its own.
 *
 * The forms it can guard are the ones the stack declares, read once and held
 * while the screen is open; what the stack is running is never asked here.
 *
 * `Concealed` for the reason every stack-facing screen here is.
 */
#[Lazy]
#[Concealed]
#[ItsContent(WhatItShowsDoes::ChangesOnItsOwn)]
final class GuardingWhileYouWatch extends NativeComponent
{
    use AsksTheStackAgain;
    use OffersTheAppsSettings;
    use AsksWhatFormsItHas;
    use FindsItsWayAround;
    use DrawsItsTemplate;

    public const string TEMPLATE = 'operator::guarding-while-you-watch';

    /**
     * The forms named so far, by name, before any guard is asked about.
     *
     * `public` for {@see HowCurrentThisStackIs::$answered}'s reason.
     *
     * @var list<string>
     */
    public array $naming = [];

    /** The guard being asked about, while the operator decides. */
    public ?AGuardAskedFor $asking = null;

    /** The guard that was started, so what it guards can be said while it runs. */
    public ?AGuardAskedFor $guarding = null;

    /** The name starting it answered. Not shown and never kept past this screen. */
    public ?string $took = null;

    /** Where the guard stands, once this frame has asked. */
    public ?HowTheGuardWent $lastGuard = null;

    public function __construct(
        private readonly Guarding $guards,
        private readonly Supervising $supervising,
        private readonly SecureStorage $storage,
        protected readonly TheWayAround $around,
        protected readonly TheAppsSettings $settings,
        protected readonly WhatItListensWith $listening,
    ) {}

    /** The forms a guard can be asked for, asked once and held while the screen is open. */
    public function answer(): TheFormsAsFound
    {
        return $this->formsFound ??= $this->askWhatFormsItHas($this->stack(), $this->storage, $this->supervising);
    }

    /** The forms a guard can be asked for, and which are named. */
    public function choosing(): WhatTheGuardWouldGuard
    {
        return new HowAGuardReads()->choosing($this->answer()->names, $this->naming);
    }

    /**
     * Name a form for the guard, or take it back out.
     *
     * Silent for a name the stack does not declare: a guard is only ever for
     * forms this screen showed.
     */
    public function choose(string $form): void
    {
        if (! in_array($form, $this->answer()->names, strict: true)) {
            return;
        }

        $this->naming = in_array($form, $this->naming, strict: true)
            ? array_values(array_filter($this->naming, static fn(string $named): bool => $named !== $form))
            : [...$this->naming, $form];
    }

    /** Ask about a guard for the forms named, where any are. */
    public function wouldGuard(): void
    {
        $guard = $this->theGuardNamed();

        if ($guard->forms()->count() > 0) {
            $this->asking = $guard;
        }
    }

    /**
     * The guard for the forms named so far, which its button asks the stack about before it is drawn.
     *
     * In the order the stack declares them, which is the order the question
     * names them in.
     */
    public function theGuardNamed(): AGuardAskedFor
    {
        $forms = [];

        foreach ($this->choosing()->forms as $form) {
            if ($form->isNamed) {
                $forms[] = Form::called($form->name);
            }
        }

        return AGuardAskedFor::of(Forms::these(...$forms));
    }

    /** Start the guard that was asked about. */
    public function agree(): void
    {
        $asked = $this->asking;

        if (! $asked instanceof AGuardAskedFor) {
            return;
        }

        $this->asking = null;
        $this->start($asked);
    }

    /** Leave it. */
    public function neverMind(): void
    {
        $this->asking = null;
    }

    /** Where the guard started here stands, or that none was. */
    public function lastGuard(): HowTheGuardWent
    {
        return $this->lastGuard ??= $this->followed();
    }

    /**
     * Ask after the guard again while it guards.
     *
     * Asking is what keeps it: the stack holds the guard only while somebody
     * asks. It does nothing once the guard has ended, so an ending is not read
     * over and over. The interval is {@see HowOftenAScreenLooks}'s constant.
     */
    #[Poll(HowOftenAScreenLooks::WHILE_WORK_RUNS_MS)]
    public function whileItGuards(): void
    {
        if ($this->lastGuard()->isGuarding) {
            $this->lastGuard = null;
        }
    }

    /** Ask the stack again where the guard stands, and the forms where they could not be read. */
    public function again(): void
    {
        $this->formsAgain();
        $this->lastGuard = null;
    }

    /** Name forms for another guard, once the last one has ended. */
    public function startOver(): void
    {
        if ($this->lastGuard()->isGuarding) {
            return;
        }

        $this->took = null;
        $this->guarding = null;
        $this->lastGuard = null;
        $this->naming = [];
    }

    /**
     * Leaving the screen lets the guard go.
     *
     * A guard held by this screen is released by its name the moment the
     * screen is left, rather than left to lapse for want of asking.
     */
    public function unmount(): void
    {
        $this->release();

        parent::unmount();
    }

    /**
     * Start the guard agreed to, and hold what to follow it by.
     *
     * A refusal is kept as what became of it, so the screen says what stood in
     * the way rather than carrying on as though a guard were running.
     */
    private function start(AGuardAskedFor $asked): void
    {
        $stack = $this->stack();
        $this->took = null;
        $this->guarding = $asked;

        $this->lastGuard = $this->storage->resume($stack->id())->either(
            held: fn(Session $session): HowTheGuardWent => $this->guards->guard($stack, $session, $asked)->either(
                started: function (Job $job) use ($asked): HowTheGuardWent {
                    $this->took = $job->shown();

                    return new HowAGuardReads()->guarding($asked->forms());
                },
                met: $this->lettingGoIfRefused($stack, new HowAGuardReads()->met(...)),
            ),
            notHeld: static fn(): HowTheGuardWent => new HowAGuardReads()->signedOut(),
        );
    }

    /** Ask the stack where the guard stands, or say that none was started. */
    private function followed(): HowTheGuardWent
    {
        $took = $this->took;
        $asked = $this->guarding;

        if ($took === null || ! $asked instanceof AGuardAskedFor) {
            return new HowAGuardReads()->notAsked();
        }

        $stack = $this->stack();

        return $this->storage->resume($stack->id())->either(
            held: fn(Session $session): HowTheGuardWent => $this->read(
                $this->guards->whatBecameOf($stack, $session, Job::named($took)),
                $asked,
                $stack,
            ),
            notHeld: static fn(): HowTheGuardWent => new HowAGuardReads()->signedOut(),
        );
    }

    /** Where the guard stands, as the template draws it. */
    private function read(HowTheGuardIsGoing $going, AGuardAskedFor $asked, Stack $stack): HowTheGuardWent
    {
        return $going->either(
            guarding: static fn(): HowTheGuardWent => new HowAGuardReads()->guarding($asked->forms()),
            sawItGo: static fn(WhatTheGuardSaw $saw): HowTheGuardWent => new HowAGuardReads()->sawItGo($saw),
            refused: static fn(string $said): HowTheGuardWent => new HowAGuardReads()->refused($said),
            ended: static fn(): HowTheGuardWent => new HowAGuardReads()->ended(),
            unknown: static fn(): HowTheGuardWent => new HowAGuardReads()->unknown(),
            met: $this->lettingGoIfRefused($stack, new HowAGuardReads()->met(...)),
        );
    }

    /**
     * Let go of a guard this screen still holds.
     *
     * Only one it last knew to be guarding, or had just started: a guard
     * that has ended has nothing to release. What the stack answers is not
     * drawn, because the screen asking is the one being left.
     */
    private function release(): void
    {
        $took = $this->took;
        $last = $this->lastGuard;

        if ($took === null || ($last instanceof HowTheGuardWent && ! $last->isGuarding)) {
            return;
        }

        $stack = $this->stack();

        $this->storage->resume($stack->id())->either(
            held: fn(Session $session): HowTheGuardIsGoing => $this->guards->letGo($stack, $session, Job::named($took)),
            notHeld: static fn(): HowTheGuardIsGoing => HowTheGuardIsGoing::ended(),
        );
    }

}
