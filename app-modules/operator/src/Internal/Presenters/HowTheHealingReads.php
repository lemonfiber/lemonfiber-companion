<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\Presenters;

use Modules\Design\View\Tone;
use Modules\Kernel\Api\ADownloadInTheQueue;
use Modules\Kernel\Api\AHealingAct;
use Modules\Kernel\Api\AHealingRun;
use Modules\Kernel\Api\AnActTaken;
use Modules\Kernel\Api\AnItemActedOn;
use Modules\Kernel\Api\AWedgedItem;
use Modules\Kernel\Api\HowTheQueueHeals;
use Modules\Kernel\Api\Instant;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\WhatBecameOfAnAct;
use Modules\Kernel\Api\WhenItSays;
use Modules\Operator\Internal\AsText;
use Modules\Operator\Internal\ViewModels\AHealingRunAsShown;
use Modules\Operator\Internal\ViewModels\AnActAsShown;
use Modules\Operator\Internal\ViewModels\AnItemActedOnAsShown;
use Modules\Operator\Internal\ViewModels\AQueuedDownloadAsShown;
use Modules\Operator\Internal\ViewModels\AServiceUnreachedAsShown;
use Modules\Operator\Internal\ViewModels\AWedgedItemAsShown;
use Modules\Operator\Internal\ViewModels\HowTheReadingWent;
use Modules\Operator\Internal\ViewModels\TheHealingAsShown;

use function sprintf;

/**
 * Turns how the queue heals itself into what the queue screen draws: each item before it is acted on, and each act of every run on its own.
 */
final readonly class HowTheHealingReads
{
    /** The key for how long a new download is left alone, by its unit. */
    private const string GRACE = 'queue.grace.%s';

    /** This device no longer holds a session for that stack. */
    public function signedOut(): TheHealingAsShown
    {
        return $this->nothing(HowTheReadingWent::theSessionEnded());
    }

    /** It did not answer, and this is what the operator met. */
    public function met(Obstacle $why): TheHealingAsShown
    {
        return $this->nothing(HowTheReadingWent::somethingStopped($why));
    }

    /** The stack said how its queue heals itself. */
    public function this(HowTheQueueHeals $queue, Instant $now): TheHealingAsShown
    {
        $items = [];

        foreach ($queue as $item) {
            $items[] = $this->wedged($item, $now);
        }

        $unreachable = [];

        foreach ($queue->unreachable() as $service) {
            $unreachable[] = new AServiceUnreachedAsShown($service->service()->named(), $service->why());
        }

        $runs = [];

        foreach ($queue->runs() as $run) {
            $runs[] = $this->run($run, $now);
        }

        return new TheHealingAsShown(
            went: HowTheReadingWent::itCameBack(),
            healingSaid: $queue->healing()->saidOnTheScreen(),
            strikesNeeded: $queue->strikesNeeded(),
            graceSaid: sprintf(self::GRACE, $queue->grace()->unit()->value),
            graceCount: $queue->grace()->howMany(),
            classified: $this->since($queue->classifiedAt(), $now),
            incomplete: $queue->isIncomplete(),
            rehearsed: $queue->isARehearsal(),
            mismatch: $queue->mismatch(),
            items: $items,
            unreachable: $unreachable,
            runs: $runs,
        );
    }

    private function nothing(HowTheReadingWent $went): TheHealingAsShown
    {
        return new TheHealingAsShown($went, '', 0, '', 0, AgoAsShown::saysNothing(), incomplete: false, rehearsed: false, mismatch: '', items: [], unreachable: [], runs: []);
    }

    private function since(WhenItSays $when, Instant $now): AgoAsShown
    {
        return $when->either(
            at: static fn(Instant $at): AgoAsShown => AgoAsShown::since($at, $now),
            unsaid: static fn(): AgoAsShown => AgoAsShown::saysNothing(),
        );
    }

    private function wedged(AWedgedItem $item, Instant $now): AWedgedItemAsShown
    {
        $next = [];

        foreach ($item->next() as $act) {
            $next[] = $act->saidOnTheScreen();
        }

        return new AWedgedItemAsShown(
            download: $this->download($item->download()),
            classSaid: $item->class()->saidOnTheScreen(),
            why: $item->why(),
            strikes: $item->strikes(),
            added: $this->since($item->whenAdded(), $now),
            graceEnds: $item->graceEnds()->either(
                at: static fn(Instant $until): AgoAsShown => $until->isAfter($now) ? AgoAsShown::until($until, $now) : AgoAsShown::saysNothing(),
                unsaid: static fn(): AgoAsShown => AgoAsShown::saysNothing(),
            ),
            next: $next,
        );
    }

    private function run(AHealingRun $run, Instant $now): AHealingRunAsShown
    {
        $items = [];

        foreach ($run as $item) {
            $items[] = $this->actedOn($item);
        }

        return new AHealingRunAsShown(AgoAsShown::since($run->at(), $now), $run->wasRehearsed(), $items);
    }

    private function actedOn(AnItemActedOn $item): AnItemActedOnAsShown
    {
        $acts = [];

        foreach ($item as $act) {
            $acts[] = $this->taken($act);
        }

        return new AnItemActedOnAsShown($this->download($item->download()), $item->why(), $acts);
    }

    private function taken(AnActTaken $act): AnActAsShown
    {
        return new AnActAsShown(
            actSaid: $act->act()->saidOnTheScreen(),
            outcomeSaid: $act->outcome()->saidOnTheScreen(),
            tone: $this->tone($act->outcome()),
            insteadSaid: $act->insteadOf(
                instead: static fn(AHealingAct $instead): AsText => AsText::of($instead->saidOnTheScreen()),
                nothing: static fn(): AsText => AsText::nothing(),
            )->said,
            why: $act->why(),
        );
    }

    private function download(ADownloadInTheQueue $download): AQueuedDownloadAsShown
    {
        $titled = $download->title() !== '';

        return new AQueuedDownloadAsShown(
            name: $titled ? $download->title() : $download->release(),
            release: $titled ? $download->release() : '',
            service: $download->service()->named(),
        );
    }

    private function tone(WhatBecameOfAnAct $outcome): string
    {
        $tone = match ($outcome) {
            WhatBecameOfAnAct::Done => Tone::Fine,
            WhatBecameOfAnAct::Would => Tone::Quiet,
            WhatBecameOfAnAct::Refused => Tone::Trouble,
            WhatBecameOfAnAct::FellBack => Tone::Attention,
        };

        return $tone->value;
    }
}
