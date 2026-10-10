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
use Modules\Kernel\Api\APlugin;
use Modules\Kernel\Api\APluginInstallAgreed;
use Modules\Kernel\Api\APluginRemovalAgreed;
use Modules\Kernel\Api\APluginSource;
use Modules\Kernel\Api\APluginUpdateAgreed;
use Modules\Kernel\Api\ARefusalInItsWords;
use Modules\Kernel\Api\Concealed;
use Modules\Kernel\Api\ExtendingIt;
use Modules\Kernel\Api\ExtendingTheStack;
use Modules\Kernel\Api\HowExtendingItIsGoing;
use Modules\Kernel\Api\HowOftenAScreenLooks;
use Modules\Kernel\Api\ItsContent;
use Modules\Kernel\Api\Job;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\PluginLines;
use Modules\Kernel\Api\Scanning;
use Modules\Kernel\Api\SecureStorage;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\TheAppsSettings;
use Modules\Kernel\Api\ThePlugins;
use Modules\Kernel\Api\WhatItShowsDoes;
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
 * The plugins on this machine, and installing, updating or removing one: each rehearsed, agreed to, and followed.
 *
 * **It opens on what is installed**, each plugin with where it came from and
 * whether anybody reviewed it, and how its source stands now. A record that
 * cannot be read is said as that, never as a machine with no plugins.
 *
 * **Every act begins with a rehearsal.** Installing begins with a source the
 * operator types; updating and removing begin from a plugin's row. The stack
 * says what the act would do, writing nothing, and a plugin it refuses, a
 * native one among them, is its refusal in its own words with nothing
 * offered to do.
 *
 * **Two agreements where a value would leave.** Each value an install's or
 * an update's recipes would carry elsewhere has a switch of its own, all off;
 * the yes agrees to the act. Choosing another act lets go of the rehearsal
 * and every approval given against it.
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
    use ReadsACode;

    public const string TEMPLATE = 'operator::what-extends-this-stack';

    /** Where the plugin comes from, as the operator is typing it. */
    public string $source = '';

    /** Whether a source is being typed, rather than what is installed being shown. */
    public bool $typing = false;

    /** What is installed, as last read, which updating and removing choose a plugin from. */
    public ?ThePlugins $listing = null;

    /** The act in hand, as its own word, or empty where none is. */
    public string $act = '';

    /** The plugin an update or a removal is about, while one is in hand. */
    public ?APlugin $subject = null;

    /** The rehearsal, while it is in front of the operator to be agreed to. */
    public ?ThePlugins $rehearsal = null;

    /** The source an install's rehearsal was asked about, which the yes names again. */
    public string $rehearsedFrom = '';

    /** @var list<string> every value approved against the rehearsal, as it spells each */
    public array $approved = [];

    /** The handle of the work being followed, while there is one. Not shown and never kept past this screen. */
    public ?string $following = null;

    /** Whether the work asked about is the act agreed to rather than its rehearsal. */
    public bool $agreed = false;

    /** Where it has got to, once this frame has asked. Public for {@see WhatIsRunningHere::$answered}'s reason. */
    public ?WhatExtendsItTurnedOutToBe $answered = null;

    public function __construct(
        private readonly ExtendingTheStack $extending,
        private readonly SecureStorage $storage,
        protected readonly TheWayAround $around,
        protected readonly TheAppsSettings $settings,
        protected readonly WhatItListensWith $listening,
        private readonly Scanning $camera,
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
        $this->act = ExtendingIt::Install->value;
        $this->typing = true;
    }

    /** Ask what installing from the typed source would do. A blank source asks nothing. */
    /** Read where the plugin comes from off a code somebody shows, into the field, for the operator to rehearse as typed. */
    public function scanWhereItComesFrom(): void
    {
        $this->readACode($this->camera, function (string $payload): void {
            $this->source = trim($payload);
        });
    }

    public function rehearse(): void
    {
        if (is_string($this->following) || trim($this->source) === '') {
            return;
        }

        $source = APluginSource::typed($this->source);
        $this->letGoOfTheRehearsal();
        $this->act = ExtendingIt::Install->value;
        $this->rehearsedFrom = $source->said();

        $this->answered = $this->put(
            fn(Stack $stack, Session $session): HowExtendingItIsGoing => $this->extending->rehearseInstalling($stack, $session, $source),
        );
    }

    /** Ask what updating the plugin in that row would do. A row it does not have, or a plugin with no source to fetch, asks nothing. */
    public function updateOne(int $which): void
    {
        $this->rehearseOnTheRow($which, ExtendingIt::Update);
    }

    /** Ask what removing the plugin in that row would do. A row it does not have asks nothing. */
    public function removeOne(int $which): void
    {
        $this->rehearseOnTheRow($which, ExtendingIt::Remove);
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
     * Agree to the act in hand, against the rehearsal on the screen and the values approved.
     *
     * Silent where no rehearsal is held: there is nothing to agree to.
     */
    public function agree(): void
    {
        $rehearsal = $this->rehearsal;
        $act = ExtendingIt::tryFrom($this->act);

        if (! $rehearsal instanceof ThePlugins || $rehearsal->agreement() === '' || ! $act instanceof ExtendingIt) {
            return;
        }

        $asking = $this->theYes($rehearsal, $act);
        $this->rehearsal = null;
        $this->approved = [];
        $this->agreed = true;
        $this->answered = $this->put($asking);
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

    /**
     * The yes to the act in hand, as the stack is asked it.
     *
     * Built before anything is let go of, so it quotes the rehearsal on the
     * screen and nothing else.
     *
     * @return Closure(Stack, Session): HowExtendingItIsGoing
     */
    private function theYes(ThePlugins $rehearsal, ExtendingIt $act): Closure
    {
        $approved = PluginLines::under('approved', ...$this->approved);
        $subject = $this->subject;

        if ($act === ExtendingIt::Install || ! $subject instanceof APlugin) {
            $agreed = APluginInstallAgreed::after($rehearsal, APluginSource::typed($this->rehearsedFrom), $approved);

            return fn(Stack $stack, Session $session): HowExtendingItIsGoing => $this->extending->install($stack, $session, $agreed);
        }

        if ($act === ExtendingIt::Update) {
            $agreed = APluginUpdateAgreed::after($rehearsal, $subject, $approved);

            return fn(Stack $stack, Session $session): HowExtendingItIsGoing => $this->extending->update($stack, $session, $agreed);
        }

        $removal = APluginRemovalAgreed::after($rehearsal, $subject);

        return fn(Stack $stack, Session $session): HowExtendingItIsGoing => $this->extending->remove($stack, $session, $removal);
    }

    /**
     * Rehearse updating or removing the plugin in that row of the listing last read.
     *
     * Nothing is asked while other work is followed, for a row the listing
     * does not have, or for an update of a plugin whose record names no
     * source to fetch again.
     */
    private function rehearseOnTheRow(int $which, ExtendingIt $act): void
    {
        $chosen = null;
        $at = 0;

        foreach ($this->listing?->installed() ?? [] as $plugin) {
            $chosen = $at === $which ? $plugin : $chosen;
            $at++;
        }

        if (is_string($this->following) || ! $chosen instanceof APlugin || ($act === ExtendingIt::Update && ! $chosen->canBeUpdated())) {
            return;
        }

        $this->letGoOfTheRehearsal();
        $this->act = $act->value;
        $this->subject = $chosen;
        $this->answered = $this->put(
            fn(Stack $stack, Session $session): HowExtendingItIsGoing => $act === ExtendingIt::Update
                ? $this->extending->rehearseUpdating($stack, $session, $chosen)
                : $this->extending->rehearseRemoving($stack, $session, $chosen),
        );
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
            $rehearsal instanceof ThePlugins => new HowExtendingItReads()->answered($rehearsal, $this->approved, agreed: false),
            $this->typing => new HowExtendingItReads()->typing(),
            default => $this->installedNow(),
        };
    }

    /** What is installed, asked now, or what the operator met instead. */
    private function installedNow(): WhatExtendsItTurnedOutToBe
    {
        $stack = $this->stack();
        $listed = function (ThePlugins $plugins): WhatExtendsItTurnedOutToBe {
            $this->listing = $plugins;

            return new HowExtendingItReads()->answered($plugins, [], agreed: false);
        };
        $met = $this->lettingGoIfRefused($stack, static fn(Obstacle $why): WhatExtendsItTurnedOutToBe => new HowExtendingItReads()->met($why, agreed: false));

        return $this->storage->resume($stack->id())->either(
            held: fn(Session $session): WhatExtendsItTurnedOutToBe => $this->extending->installedOn($stack, $session)->either(found: $listed, met: $met),
            notHeld: static fn(): WhatExtendsItTurnedOutToBe => new HowExtendingItReads()->signedOut(),
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
        $agreed = $this->agreed;
        $act = ExtendingIt::tryFrom($this->act) ?? ExtendingIt::Install;

        return $going->either(
            underway: function (Job $job) use ($act, $agreed): WhatExtendsItTurnedOutToBe {
                $this->following = $job->shown();

                return new HowExtendingItReads()->running($act, $agreed);
            },
            done: function (ThePlugins $plugins) use ($agreed): WhatExtendsItTurnedOutToBe {
                $this->following = null;
                $this->rehearsal = ! $agreed && $plugins->agreement() !== '' ? $plugins : null;

                return new HowExtendingItReads()->answered($plugins, $this->approved, $agreed);
            },
            refused: function (ARefusalInItsWords $why) use ($act, $agreed): WhatExtendsItTurnedOutToBe {
                $this->following = null;

                return new HowExtendingItReads()->refused($why, $act, $agreed);
            },
            ended: function () use ($agreed): WhatExtendsItTurnedOutToBe {
                $this->following = null;

                return new HowExtendingItReads()->ended($agreed);
            },
            met: function (Obstacle $why) use ($stack, $agreed): WhatExtendsItTurnedOutToBe {
                $this->letGoOfTheSession($why, $stack);

                return new HowExtendingItReads()->met($why, $agreed);
            },
        );
    }

    /** Let go of the rehearsal, every approval given against it and the work it followed, so the next is agreed to afresh. */
    private function letGoOfTheRehearsal(): void
    {
        $this->rehearsal = null;
        $this->rehearsedFrom = '';
        $this->approved = [];
        $this->act = '';
        $this->subject = null;
        $this->agreed = false;
        $this->typing = false;
        $this->answered = null;
    }
}
