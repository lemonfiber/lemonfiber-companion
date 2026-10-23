<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\Screens;

use Closure;
use Illuminate\View\View;

use function is_string;

use Modules\Connection\Api\LetsGoOfARefusedSession;
use Modules\Design\Api\WritesItsTaps;
use Modules\Kernel\Api\ARefusalInItsWords;
use Modules\Kernel\Api\AskingTheIndexers;
use Modules\Kernel\Api\Concealed;
use Modules\Kernel\Api\Explaining;
use Modules\Kernel\Api\HowOftenAScreenLooks;
use Modules\Kernel\Api\ItsContent;
use Modules\Kernel\Api\Job;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\SecureStorage;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\TheAppsSettings;
use Modules\Kernel\Api\Tracing;
use Modules\Kernel\Api\WhatBecameOfTheWork;
use Modules\Kernel\Api\WhatItShowsDoes;
use Modules\Kernel\Api\WhatToFollow;
use Modules\Kernel\Api\WhereItGotTo;
use Modules\Operator\Internal\AwaitsAnOutcome;
use Modules\Operator\Internal\LooksAgainWhileItMoves;
use Modules\Operator\Internal\OffersTheAppsSettings;
use Modules\Operator\Internal\Presenters\HowATraceReads;
use Modules\Operator\Internal\Presenters\HowTheRunReads;
use Modules\Operator\Internal\ReadsAStackOnceAFrame;
use Modules\Operator\Internal\ShowsWhatItsWordsMean;
use Modules\Operator\Internal\ViewModels\TheRunTurnedOutToBe;
use Modules\Operator\Internal\ViewModels\TheTraceTurnedOutToBe;
use Modules\Wayfinding\Api\Screens\AsksAgain;
use Modules\Wayfinding\Api\Screens\AsksTheStackAgain;
use Modules\Wayfinding\Api\TheWayAround;
use Modules\Wayfinding\Api\WhatItListensWith;
use Native\Mobile\Attributes\Lazy;
use Native\Mobile\Attributes\Poll;
use Native\Mobile\Edge\NativeComponent;

use function trim;
use function view;

/**
 * Where one item got to: the answer to *where is my show?*.
 *
 * Opened on what the route names, and able to follow anything else typed into
 * it. How sure the trace is comes first, since a guess drawn as fact is
 * worse than a marked one; then how far it got and why it stopped, what has
 * been tried, where the services disagree, and for a series what is here and
 * outstanding season by season. Nothing asked for is its own answer.
 *
 * This is the operator's trace. It carries the pipeline's internals and is
 * not a member's view of their own request.
 *
 * `Concealed` for the reason every stack-facing screen here is.
 */
#[Lazy]
#[Concealed]
#[ItsContent(WhatItShowsDoes::ChangesOnItsOwn)]
final class WhereThisGotTo extends NativeComponent implements AwaitsAnOutcome
{
    use WritesItsTaps;
    use AsksTheStackAgain;
    use LooksAgainWhileItMoves;
    use OffersTheAppsSettings;
    use LetsGoOfARefusedSession;
    use ShowsWhatItsWordsMean;
    use ReadsAStackOnceAFrame;
    use FindsItsWayAround;
    use AsksAgain;

    /** What was typed and followed instead of what the route names, or empty. */
    public string $following = '';

    /** What is typed into the box, followed only when asked to. Public for {@see WhatThisServiceSaid::$looking}'s reason. */
    public string $looking = '';

    /** What came back, once the frame has asked. */
    public ?TheTraceTurnedOutToBe $answered = null;

    /** The season the trace is narrowed to, or nothing for every season. */
    public ?int $season = null;

    /** The handle of the search being followed, where there is one. */
    public ?string $searching = null;

    public ?TheRunTurnedOutToBe $going = null;

    public function __construct(
        private readonly Tracing $tracing,
        private readonly AskingTheIndexers $indexers,
        private readonly Explaining $explaining,
        private readonly SecureStorage $storage,
        protected readonly TheWayAround $around,
        protected readonly TheAppsSettings $settings,
        protected readonly WhatItListensWith $listening,
    ) {}

    /** Follow what was typed instead; nothing typed follows nothing new. */
    public function follow(): void
    {
        if (trim($this->looking) === '') {
            return;
        }

        $this->following = trim($this->looking);
        $this->looking = '';
        $this->answered = null;
    }

    /** Follow one season of the item; a season below none follows nothing new. */
    public function followSeason(int $season): void
    {
        if ($season < 0) {
            return;
        }

        $this->season = $season;
        $this->answered = null;
    }

    /** Follow every season of the item again. */
    public function followEverySeason(): void
    {
        $this->season = null;
        $this->answered = null;
    }

    /**
     * Ask the indexers about what the trace followed, with the season it was read with.
     *
     * Only where the trace the screen drew says a search would settle it.
     */
    public function search(): void
    {
        $term = $this->term();

        if ($term === '' || $this->answer()->searchSpends === '' || $this->howTheSearchIsGoing()->isWorking) {
            return;
        }

        $following = $this->following($term);
        $this->going = $this->searched(fn(Stack $stack, Session $session): WhatBecameOfTheWork => $this->indexers->search($stack, $session, $following));
    }

    #[Poll(HowOftenAScreenLooks::WHILE_WORK_RUNS_MS)]
    public function whileItRuns(): void
    {
        if ($this->howTheSearchIsGoing()->isWorking) {
            $this->going = null;
        }
    }

    public function awaitsAnOutcome(): bool
    {
        return $this->howTheSearchIsGoing()->isWorking;
    }

    public function howTheSearchIsGoing(): TheRunTurnedOutToBe
    {
        $searching = $this->searching;

        return $this->going ??= $searching === null
            ? new HowTheRunReads()->notAsked()
            : $this->searched(fn(Stack $stack, Session $session): WhatBecameOfTheWork => $this->indexers->whatBecameOf($stack, $session, Job::named($searching)));
    }

    public function render(): View
    {
        $this->aFrameBegins();

        return view('operator::where-this-got-to', ['looking' => $this->looking]);
    }

    /** What came back, asked once per frame; a search that came back replaces it, so the search is asked after first. */
    public function answer(): TheTraceTurnedOutToBe
    {
        $this->howTheSearchIsGoing();

        if (! $this->answered instanceof TheTraceTurnedOutToBe) {
            $this->readsItsStack();
            $this->answered = $this->ask();
        }

        return $this->answered;
    }

    /** Where this screen's words are explained from. */
    protected function explaining(): Explaining
    {
        return $this->explaining;
    }

    /** Resume the session, follow the item, and flatten what came back. */
    private function ask(): TheTraceTurnedOutToBe
    {
        $term = $this->term();

        if ($term === '') {
            return new HowATraceReads()->nothingToFollow();
        }

        $stack = $this->stack();

        return $this->storage->resume($stack->id())->either(
            held: fn(Session $session): TheTraceTurnedOutToBe => $this->asked($stack, $session, $this->following($term)),
            notHeld: static fn(): TheTraceTurnedOutToBe => new HowATraceReads()->signedOut($term),
        );
    }

    /**
     * What is followed: what was typed and followed, or else what the route names.
     *
     * Read from the route on every frame, for {@see Stack()}'s reason.
     */
    private function term(): string
    {
        if ($this->following !== '') {
            return $this->following;
        }

        $named = $this->param('service');

        return is_string($named) ? trim($named) : '';
    }

    /** What is followed, narrowed to the season the screen holds where it holds one. */
    private function following(string $term): WhatToFollow
    {
        return $this->season === null ? WhatToFollow::called($term) : WhatToFollow::calledInSeason($term, $this->season);
    }

    /** @param Closure(Stack, Session): WhatBecameOfTheWork<WhereItGotTo> $asking */
    private function searched(Closure $asking): TheRunTurnedOutToBe
    {
        $stack = $this->stack();

        return $this->storage->resume($stack->id())->either(
            held: fn(Session $session): TheRunTurnedOutToBe => $this->shown($asking($stack, $session), $stack),
            notHeld: static fn(): TheRunTurnedOutToBe => new HowTheRunReads()->signedOut(),
        );
    }

    /**
     * Where the search has got to; once it answers, the trace it came back with is what the screen draws.
     *
     * @param WhatBecameOfTheWork<WhereItGotTo> $became
     */
    private function shown(WhatBecameOfTheWork $became, Stack $stack): TheRunTurnedOutToBe
    {
        return $became->either(
            underway: function (Job $job): TheRunTurnedOutToBe {
                $this->searching = $job->shown();

                return new HowTheRunReads()->running();
            },
            answered: function (WhereItGotTo $trace): TheRunTurnedOutToBe {
                $this->searching = null;
                $this->answered = new HowATraceReads()->this($trace);

                return new HowTheRunReads()->answered();
            },
            ended: function (): TheRunTurnedOutToBe {
                $this->searching = null;

                return new HowTheRunReads()->ended();
            },
            refused: function (ARefusalInItsWords $why): TheRunTurnedOutToBe {
                $this->searching = null;

                return new HowTheRunReads()->refused($why);
            },
            met: function (Obstacle $why) use ($stack): TheRunTurnedOutToBe {
                $this->letGoOfTheSession($why, $stack);

                return new HowTheRunReads()->met($why);
            },
        );
    }

    /** Where the item got to, or what the operator met instead. */
    private function asked(Stack $stack, Session $session, WhatToFollow $following): TheTraceTurnedOutToBe
    {
        return $this->tracing->tracedOn($stack, $session, $following)->either(
            found: static fn(WhereItGotTo $trace): TheTraceTurnedOutToBe => new HowATraceReads()->this($trace),
            met: function (Obstacle $why) use ($stack, $following): TheTraceTurnedOutToBe {
                $this->letGoOfTheSession($why, $stack);

                return new HowATraceReads()->met($why, $following->term());
            },
        );
    }
}
