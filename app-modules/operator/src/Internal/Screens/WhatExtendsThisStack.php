<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\Screens;

use function array_filter;
use function array_key_exists;
use function array_values;

use Closure;

use function in_array;
use function is_string;

use Modules\Connection\Api\LetsGoOfARefusedSession;
use Modules\Kernel\Api\APluginInstallAgreed;
use Modules\Kernel\Api\APluginSource;
use Modules\Kernel\Api\ARefusalInItsWords;
use Modules\Kernel\Api\Concealed;
use Modules\Kernel\Api\ExtendingTheStack;
use Modules\Kernel\Api\HowExtendingItIsGoing;
use Modules\Kernel\Api\HowOftenAScreenLooks;
use Modules\Kernel\Api\ItsContent;
use Modules\Kernel\Api\Job;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\PluginLines;
use Modules\Kernel\Api\SecureStorage;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\TheAppsSettings;
use Modules\Kernel\Api\ThePlugins;
use Modules\Kernel\Api\WhatItShowsDoes;
use Modules\Kernel\Api\WhatWasFoundOfThePlugins;
use Modules\Operator\Internal\AwaitsAnOutcome;
use Modules\Operator\Internal\OffersTheAppsSettings;
use Modules\Operator\Internal\Presenters\HowExtendingItReads;
use Modules\Operator\Internal\ViewModels\WhatExtendsItTurnedOutToBe;
use Modules\Wayfinding\Api\Screens\AsksTheStackAgain;
use Modules\Wayfinding\Api\Screens\DrawsItsTemplate;
use Modules\Wayfinding\Api\TheWayAround;
use Modules\Wayfinding\Api\WhatItListensWith;
use Native\Mobile\Attributes\Lazy;
use Native\Mobile\Attributes\Poll;
use Native\Mobile\Edge\NativeComponent;

use function trim;

/**
 * The plugins on this machine, and installing one: rehearsed, agreed to in two parts, and followed.
 *
 * **It opens on what is installed**, each plugin with where it came from and
 * whether anybody reviewed it, and how its source stands now. A record that
 * cannot be read is said as that, never as a machine with no plugins.
 *
 * **Installing begins with a source and a rehearsal.** The operator types where
 * the plugin comes from, and the stack says what installing it would do,
 * writing nothing: every change, every proof, every setting it overrides,
 * everything it would leave contested, and every recipe in full. A plugin the
 * stack refuses, a native one among them, is its refusal in its own words,
 * and nothing is offered to install.
 *
 * **Two agreements, never one.** Each value a recipe would carry elsewhere has
 * a switch of its own, all off; Install agrees to the install. Install is
 * offered with no value approved, and what that comes to is the stack's
 * answer. Changing the source lets go of the rehearsal and every approval
 * given against it.
 *
 * **No inputs are taken.** A recipe that asks the operator for a value is
 * shown in full like any other, and installing it is for the web console or
 * the terminal, which the screen says beneath Install.
 *
 * `Concealed` for the reason every stack-facing screen here is.
 */
#[Lazy]
#[Concealed]
#[ItsContent(WhatItShowsDoes::ChangesOnItsOwn)]
final class WhatExtendsThisStack extends NativeComponent implements AwaitsAnOutcome
{
    use AsksTheStackAgain;
    use OffersTheAppsSettings;
    use LetsGoOfARefusedSession;
    use FindsItsWayAround;
    use DrawsItsTemplate;

    public const string TEMPLATE = 'operator::what-extends-this-stack';

    /** Where the plugin comes from, as the operator is typing it. */
    public string $source = '';

    /** Whether a source is being typed, rather than what is installed being shown. */
    public bool $typing = false;

    /** The rehearsal, while it is in front of the operator to be agreed to. */
    public ?ThePlugins $rehearsal = null;

    /** The source the rehearsal was asked about, which the yes names again. */
    public string $rehearsedFrom = '';

    /** @var list<string> every value approved against the rehearsal, as it spells each */
    public array $approved = [];

    /** The handle of the work being followed, while there is one. Not shown and never kept past this screen. */
    public ?string $following = null;

    /** Whether the work asked about is the install rather than its rehearsal. */
    public bool $installing = false;

    /** Where it has got to, once this frame has asked. Public for {@see WhatIsRunningHere::$answered}'s reason. */
    public ?WhatExtendsItTurnedOutToBe $answered = null;

    public function __construct(
        private readonly ExtendingTheStack $extending,
        private readonly SecureStorage $storage,
        protected readonly TheWayAround $around,
        protected readonly TheAppsSettings $settings,
        protected readonly WhatItListensWith $listening,
    ) {}

    /** The same question this screen's cadence asks, answered from what it last heard. */
    public function awaitsAnOutcome(): bool
    {
        return $this->answer()->isWorking;
    }

    /** Where it has got to, asked once per frame. */
    public function answer(): WhatExtendsItTurnedOutToBe
    {
        return $this->answered ??= $this->asked();
    }

    /** Begin installing one: type where it comes from. */
    public function installOne(): void
    {
        if (is_string($this->following)) {
            return;
        }

        $this->letGoOfTheRehearsal();
        $this->typing = true;
    }

    /** Ask what installing from the typed source would do. A blank source asks nothing. */
    public function rehearse(): void
    {
        if (is_string($this->following) || trim($this->source) === '') {
            return;
        }

        $source = APluginSource::typed($this->source);
        $this->letGoOfTheRehearsal();
        $this->rehearsedFrom = $source->said();

        $this->answered = $this->put(
            fn(Stack $stack, Session $session): HowExtendingItIsGoing => $this->extending->rehearseInstalling($stack, $session, $source),
        );
    }

    /**
     * Approve one value a recipe would carry elsewhere, or take the approval back.
     *
     * Named by its place among the rehearsal's approvals. A place it does not
     * have changes nothing.
     */
    public function approve(int $which): void
    {
        $rehearsal = $this->rehearsal;

        if (! $rehearsal instanceof ThePlugins) {
            return;
        }

        // Collected by hand, so a place is the one the screen drew.
        $approvals = [];

        foreach ($rehearsal->approvals() as $approval) {
            $approvals[] = $approval;
        }

        if (! array_key_exists($which, $approvals)) {
            return;
        }

        $approval = $approvals[$which];
        $this->approved = in_array($approval, $this->approved, strict: true)
            ? array_values(array_filter($this->approved, static fn(string $given): bool => $given !== $approval))
            : [...$this->approved, $approval];
        $this->answered = null;
    }

    /**
     * Install it, against the rehearsal on the screen and the values approved.
     *
     * Silent where no rehearsal is held: there is nothing to agree to.
     */
    public function install(): void
    {
        $rehearsal = $this->rehearsal;

        if (! $rehearsal instanceof ThePlugins || $rehearsal->agreement() === '') {
            return;
        }

        $agreed = APluginInstallAgreed::after($rehearsal, APluginSource::typed($this->rehearsedFrom), PluginLines::under('approved', ...$this->approved));
        $this->letGoOfTheRehearsal();
        $this->installing = true;

        $this->answered = $this->put(
            fn(Stack $stack, Session $session): HowExtendingItIsGoing => $this->extending->install($stack, $session, $agreed),
        );
    }

    /** Back to what is installed, letting go of any source, rehearsal and approval. */
    public function backToThePlugins(): void
    {
        if (is_string($this->following)) {
            return;
        }

        $this->letGoOfTheRehearsal();
        $this->source = '';
    }

    /**
     * Ask again, because the operator said so.
     *
     * After work the stack took on, it asks after the same handle. Otherwise
     * it reads what is installed afresh.
     */
    public function again(): void
    {
        $this->answered = null;

        if (is_string($this->following)) {
            return;
        }

        $this->letGoOfTheRehearsal();
    }

    /**
     * Ask after the work again while the stack is at it.
     *
     * Nothing happens unless it is running. The interval is
     * {@see HowOftenAScreenLooks}'s.
     */
    #[Poll(HowOftenAScreenLooks::WHILE_WORK_RUNS_MS)]
    public function whileItRuns(): void
    {
        if ($this->answer()->isWorking) {
            $this->answered = null;
        }
    }

    /** What the stack is asked this frame: after the work, the rehearsal held, or what is installed. */
    private function asked(): WhatExtendsItTurnedOutToBe
    {
        $following = $this->following;

        if (is_string($following)) {
            return $this->put(
                fn(Stack $stack, Session $session): HowExtendingItIsGoing => $this->extending->whatBecameOf($stack, $session, Job::named($following)),
            );
        }

        $rehearsal = $this->rehearsal;

        return match (true) {
            $rehearsal instanceof ThePlugins => new HowExtendingItReads()->answered($rehearsal, $this->approved, installing: false),
            $this->typing => new HowExtendingItReads()->typing(),
            default => $this->installedNow(),
        };
    }

    /** What is installed, asked now. */
    private function installedNow(): WhatExtendsItTurnedOutToBe
    {
        $stack = $this->stack();

        return $this->storage->resume($stack->id())->either(
            held: fn(Session $session): WhatExtendsItTurnedOutToBe => $this->listed($this->extending->installedOn($stack, $session), $stack),
            notHeld: static fn(): WhatExtendsItTurnedOutToBe => new HowExtendingItReads()->signedOut(),
        );
    }

    /** What is installed, or what the operator met instead. */
    private function listed(WhatWasFoundOfThePlugins $found, Stack $stack): WhatExtendsItTurnedOutToBe
    {
        return $found->either(
            found: static fn(ThePlugins $plugins): WhatExtendsItTurnedOutToBe => new HowExtendingItReads()->answered($plugins, [], installing: false),
            met: $this->lettingGoIfRefused($stack, static fn(Obstacle $why): WhatExtendsItTurnedOutToBe => new HowExtendingItReads()->met($why, installing: false)),
        );
    }

    /**
     * The work put to the stack, with the session this device holds for it.
     *
     * @param Closure(Stack, Session): HowExtendingItIsGoing $asking
     */
    private function put(Closure $asking): WhatExtendsItTurnedOutToBe
    {
        $stack = $this->stack();

        return $this->storage->resume($stack->id())->either(
            held: fn(Session $session): WhatExtendsItTurnedOutToBe => $this->shown($asking($stack, $session), $stack),
            notHeld: static fn(): WhatExtendsItTurnedOutToBe => new HowExtendingItReads()->signedOut(),
        );
    }

    /** What the stack said of the work, as the screen draws it, holding what to follow and the rehearsal to agree to. */
    private function shown(HowExtendingItIsGoing $going, Stack $stack): WhatExtendsItTurnedOutToBe
    {
        $installing = $this->installing;

        return $going->either(
            underway: function (Job $job) use ($installing): WhatExtendsItTurnedOutToBe {
                $this->following = $job->shown();

                return new HowExtendingItReads()->running($installing);
            },
            done: function (ThePlugins $plugins) use ($installing): WhatExtendsItTurnedOutToBe {
                $this->following = null;
                $this->rehearsal = ! $installing && $plugins->agreement() !== '' ? $plugins : null;

                return new HowExtendingItReads()->answered($plugins, $this->approved, $installing);
            },
            refused: function (ARefusalInItsWords $why) use ($installing): WhatExtendsItTurnedOutToBe {
                $this->following = null;

                return new HowExtendingItReads()->refused($why, $installing);
            },
            ended: function () use ($installing): WhatExtendsItTurnedOutToBe {
                $this->following = null;

                return new HowExtendingItReads()->ended($installing);
            },
            met: function (Obstacle $why) use ($stack, $installing): WhatExtendsItTurnedOutToBe {
                $this->letGoOfTheSession($why, $stack);

                return new HowExtendingItReads()->met($why, $installing);
            },
        );
    }

    /** Let go of the rehearsal, every approval given against it and the work it followed, so the next is agreed to afresh. */
    private function letGoOfTheRehearsal(): void
    {
        $this->rehearsal = null;
        $this->rehearsedFrom = '';
        $this->approved = [];
        $this->installing = false;
        $this->typing = false;
        $this->answered = null;
    }
}
