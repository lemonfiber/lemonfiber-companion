<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\Presenters;

use function array_filter;
use function array_values;

use Modules\Design\View\Tone;
use Modules\Kernel\Api\Daemon;
use Modules\Kernel\Api\Daemons;
use Modules\Kernel\Api\HowAServiceRuns;
use Modules\Kernel\Api\ServiceId;
use Modules\Kernel\Api\WhatToDoWithIt;
use Modules\Operator\Internal\AsText;
use Modules\Operator\Internal\ViewModels\WhatOneServiceSays;

/**
 * What one service a stack runs comes to, as the fields a row reads.
 *
 * **What leans on it travels with the row.** A disruptive action has to
 * state what it disturbs, and what a stop disturbs is not knowable from the
 * service alone — it is the other services that will not work without it. A
 * screen that had to go back and ask would be a screen that could forget to.
 * Each is said by the name the listing gives it, which is the name the
 * operator knows it by, rather than by the identifier the stack names it by.
 */
final readonly class HowAServiceReads
{
    /**
     * Fold one service into the fields a row needs.
     *
     * The exit code is folded here rather than in the screen, for
     * {@see HowAStalledItemReads::in()}'s reason: one closure builds the whole
     * row, so a field cannot reach a template by a path that skipped the value
     * object.
     */
    public function in(Daemon $daemon, Daemons $listing): WhatOneServiceSays
    {
        $leaning = [];

        foreach ($daemon->whatLeansOnIt() as $id) {
            $leaning[] = $this->nameOf($id, $listing);
        }

        $runsFor = new HowWhatWasLeftOutReads()->forms($daemon->whatBroughtItIn());
        $isInstalled = $daemon->runs() !== HowAServiceRuns::Absent || $runsFor !== [];

        return new WhatOneServiceSays(
            id: $daemon->id(),
            name: $daemon->name(),
            runsSaid: $daemon->runs()->saidOnTheScreen(),
            // Nothing is wrong with a service nobody asked for, so it is drawn
            // in the quiet tone rather than the one an absence asked for has.
            tone: $isInstalled ? $this->toneOf($daemon->runs()) : Tone::Quiet->value,
            mattersSaid: $daemon->matters()->saidOnTheScreen(),
            isSettling: $daemon->runs()->isSettling(),
            isOurs: $daemon->runs()->isThisStacksToRun(),
            wouldNotHelp: $daemon->runs()->isAlreadyBeingRestarted(),
            leaning: $leaning,
            exited: $this->exited($daemon),
            stoppedSaid: $this->stopped($daemon),
            // Asked of the state rather than worked out here, so one screen
            // cannot come to a different answer from another about what a
            // stopped service can be told to do. A walk over the three rather
            // than a list handed back, because `D1` refuses an array crossing a
            // module boundary — the decision is still the enum's and this is
            // only the shape it arrives in.
            verbs: array_values(array_filter(
                WhatToDoWithIt::cases(),
                static fn(WhatToDoWithIt $verb): bool => $daemon->runs()->mayTake($verb),
            )),
            runsFor: $runsFor,
            isInstalled: $isInstalled,
        );
    }

    /**
     * The glyph a state is drawn with beside the name of what is in it: a
     * service, or a container the stack never declared.
     *
     * A service the host runs reads as fine: the stack has no say over it, and
     * a glyph asking for attention there would ask for something nobody here
     * can give.
     */
    public function toneOf(HowAServiceRuns $runs): string
    {
        return match ($runs) {
            HowAServiceRuns::Running, HowAServiceRuns::Healthy, HowAServiceRuns::HostManaged => Tone::Fine->value,
            HowAServiceRuns::Starting => Tone::Working->value,
            HowAServiceRuns::Stopped, HowAServiceRuns::Absent => Tone::Attention->value,
            HowAServiceRuns::Failed, HowAServiceRuns::CrashLooping, HowAServiceRuns::Unhealthy => Tone::Trouble->value,
        };
    }

    /**
     * The name the listing gives a service, or its identifier where the
     * listing names no such service.
     */
    private function nameOf(ServiceId $id, Daemons $listing): string
    {
        foreach ($listing as $other) {
            if ($other->id()->isTheSameAs($id)) {
                return $other->name();
            }
        }

        return $id->named();
    }

    /** The key for how it stopped, or empty where it did not: with an error, or without one. */
    private function stopped(Daemon $daemon): string
    {
        return $daemon->exit(
            said: static fn(int $code): AsText => AsText::of($code === 0 ? 'health.it_stopped_cleanly' : 'health.it_stopped_with_an_error'),
            unstated: static fn(): AsText => AsText::nothing(),
        )->said;
    }

    /**
     * What it exited with, as text, or nothing where it did not.
     *
     * The empty string rather than a zero, because a service that is running
     * has no exit code at all and `0` is the code for one that ended well — the
     * two must not render the same.
     */
    private function exited(Daemon $daemon): string
    {
        return $daemon->exit(
            said: static fn(int $code): AsText => AsText::of((string) $code),
            unstated: static fn(): AsText => AsText::nothing(),
        )->said;
    }
}
