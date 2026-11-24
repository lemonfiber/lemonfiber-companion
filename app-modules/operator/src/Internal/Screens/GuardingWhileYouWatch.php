<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\Screens;

use function array_filter;
use function array_values;
use function in_array;

use Modules\Design\Api\WritesItsTaps;
use Modules\Kernel\Api\AGuardAskedFor;
use Modules\Kernel\Api\Concealed;
use Modules\Kernel\Api\Form;
use Modules\Kernel\Api\Forms;
use Modules\Kernel\Api\Guarding;
use Modules\Kernel\Api\HearingTheJobs;
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
use Modules\Kernel\Api\WhyTheWorkEnded;
use Modules\Operator\Internal\AsksWhatFormsItHas;
use Modules\Operator\Internal\AwaitsAnOutcome;
use Modules\Operator\Internal\FollowsOneJobAtATime;
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
use Native\Mobile\Edge\NativeComponent;
use UnitEnum;

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
final class GuardingWhileYouWatch extends NativeComponent implements AwaitsAnOutcome
{
    use WritesItsTaps;
    use AsksTheStackAgain;
    use OffersTheAppsSettings;
    use AsksWhatFormsItHas;
    /** @use FollowsOneJobAtATime<HowTheGuardWent> */
    use FollowsOneJobAtATime, FindsItsWayAround {
        FollowsOneJobAtATime::letGoOfWhatElseItHears insteadof FindsItsWayAround;
    }
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


    public function __construct(
        private readonly Guarding $guards,
        private readonly Supervising $supervising,
        private readonly SecureStorage $storage,
        protected readonly TheWayAround $around,
        protected readonly TheAppsSettings $settings,
        protected readonly WhatItListensWith $listening,
        protected readonly HearingTheJobs $hearingTheJobs,
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

    /** Never: a guard runs until the operator stops it, so there is no outcome to await, and the lock is raised over it as over any screen. */
    public function awaitsAnOutcome(UnitEnum ...$for): bool
    {
        return false;
    }

    /** Where the guard started here stands, or that none was. */
    public function lastGuard(): HowTheGuardWent
    {
        return $this->whatBecameOfTheJob();
    }

    /** Ask the stack again where the guard stands, and the forms where they could not be read. */
    public function again(): void
    {
        $this->formsAgain();
        $this->askAfterAgain();
    }

    /** Name forms for another guard, once the last one has ended. */
    public function startOver(): void
    {
        if ($this->lastGuard()->stands->isWorking()) {
            return;
        }

        $this->forget();
        $this->guarding = null;
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

    protected function whatTheJobCameTo(Stack $stack, Session $session, Job $job): HowTheGuardWent
    {
        return $this->guarding instanceof AGuardAskedFor
            ? $this->read($this->guards->whatBecameOf($stack, $session, $job), $this->guarding, $stack)
            : new HowAGuardReads()->notAsked();
    }

    protected function whileNoJobIsFollowed(): HowTheGuardWent
    {
        return new HowAGuardReads()->notAsked();
    }

    protected function withNoSessionHeld(): HowTheGuardWent
    {
        return new HowAGuardReads()->signedOut();
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
        $this->forget();
        $this->guarding = $asked;

        $this->reported($this->storage->resume($stack->id())->either(
            held: fn(Session $session): HowTheGuardWent => $this->guards->guard($stack, $session, $asked)->either(
                started: function (Job $job) use ($asked): HowTheGuardWent {
                    $this->follow($job);

                    return new HowAGuardReads()->guarding($asked->forms());
                },
                met: $this->lettingGoIfRefused($stack, new HowAGuardReads()->met(...)),
            ),
            notHeld: $this->withNoSessionHeld(...),
        ));
    }

    /** Where the guard stands, as the template draws it. */
    private function read(HowTheGuardIsGoing $going, AGuardAskedFor $asked, Stack $stack): HowTheGuardWent
    {
        return $going->either(
            guarding: static fn(): HowTheGuardWent => new HowAGuardReads()->guarding($asked->forms()),
            sawItGo: static fn(WhatTheGuardSaw $saw): HowTheGuardWent => new HowAGuardReads()->sawItGo($saw),
            refused: static fn(string $said): HowTheGuardWent => new HowAGuardReads()->refused($said),
            ended: static fn(?WhyTheWorkEnded $why): HowTheGuardWent => new HowAGuardReads()->ended($why),
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
        $guard = $this->theRunningJob();

        if (! $guard instanceof Job) {
            return;
        }

        $stack = $this->stack();

        $this->storage->resume($stack->id())->either(
            held: fn(Session $session): HowTheGuardIsGoing => $this->guards->letGo($stack, $session, $guard),
            notHeld: static fn(): HowTheGuardIsGoing => HowTheGuardIsGoing::noOutcome(),
        );
    }

}
