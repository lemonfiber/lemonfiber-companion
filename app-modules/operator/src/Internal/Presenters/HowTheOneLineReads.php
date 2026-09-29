<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\Presenters;

use Modules\Design\View\Tone;
use Modules\Health\Api\WhatWasHeardSoFar;
use Modules\Kernel\Api\AnAffectedItem;
use Modules\Kernel\Api\AStoppage;
use Modules\Kernel\Api\HowItStands;
use Modules\Kernel\Api\HowLongAgo;
use Modules\Kernel\Api\Instant;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\TheHealthSummary;
use Modules\Operator\Internal\ViewModels\AnAffectedItemAsShown;
use Modules\Operator\Internal\ViewModels\AStoppageAsShown;
use Modules\Operator\Internal\ViewModels\WhatTheOneLineSays;
use Modules\Operator\Internal\ViewModels\WhyNothingWasSaid;

/**
 * The health summary a screen holds, as the lines a template draws.
 *
 * **A summary that is not current reads as unknown.** Whatever it said, it said
 * it before the subscription broke or went quiet, and a stack nobody can vouch
 * for right now is not healthy. What it said is still shown, with when, so the
 * operator knows what was last true.
 *
 * **How many is said the way the word asks for.** Notes where the word is
 * advisory, because an advisory stack is working and nothing on it needs
 * anybody. Things needing attention where the word is degraded, broken or
 * critical. Things reported anywhere else, because a stack that is stopped or
 * still starting can carry findings without any of them being a demand.
 *
 * **The list says the same line from what was kept.** It holds no stream, so
 * it has the word a stack's screen last heard and when, and nothing else. That
 * word is said for as long as a stream could have stayed silent and still been
 * vouched for, and past that it reads as unknown, as the stack's own screen
 * would; either way it says when it was heard. A row has room for a word
 * and not a sentence, so every line carries its word as a single word too.
 */
final readonly class HowTheOneLineReads
{
    /** The key for a screen that has opened its subscription and heard nothing yet. */
    private const string WAITING = 'health.summary.waiting';

    public function of(WhatWasHeardSoFar $heard, Instant $now): WhatTheOneLineSays
    {
        $why = $heard->stoppedBy(
            nothing: static fn(): WhyNothingWasSaid => WhyNothingWasSaid::nothingStoppedIt(),
            met: static fn(Obstacle $why): WhyNothingWasSaid => WhyNothingWasSaid::because($why),
        );

        return $heard->summary(
            none: fn(): WhatTheOneLineSays => $this->nothingHeard($why),
            current: fn(TheHealthSummary $summary): WhatTheOneLineSays
                => $this->said($summary, $summary->standing(), AgoAsShown::live(), $why),
            asOf: fn(TheHealthSummary $summary, Instant $at): WhatTheOneLineSays
                => $this->said($summary, HowItStands::Unknown, AgoAsShown::from(HowLongAgo::since($at, $now), $at, $now), $why),
        );
    }

    /** What the list says for a stack whose one line was heard at `$at`, and kept. */
    public function kept(HowItStands $standing, Instant $at, Instant $now): WhatTheOneLineSays
    {
        $shown = WhatWasHeardSoFar::isStillCurrent($at, $now) ? $standing : HowItStands::Unknown;

        return $this->aloneOnTheList($shown, AgoAsShown::from(HowLongAgo::since($at, $now), $at, $now));
    }

    /**
     * What the list says for a stack whose one line has never been heard.
     *
     * Unknown, in its own sentence: the list cannot say how the stack is, and
     * a row saying nothing would read as nothing being wrong.
     */
    public function neverHeard(): WhatTheOneLineSays
    {
        return $this->aloneOnTheList(HowItStands::Unknown, AgoAsShown::live());
    }

    /** A word with no count, no worst thing and no stream, which is all the list has. */
    private function aloneOnTheList(HowItStands $shown, AgoAsShown $ago): WhatTheOneLineSays
    {
        return new WhatTheOneLineSays(
            said: $shown->saidOnTheScreen(),
            word: $shown->saidInAWord(),
            tone: $this->toneOf($shown),
            counted: '',
            howMany: 0,
            worst: '',
            ago: $ago,
            met: '',
            remedy: '',
            affected: [],
            stopped: [],
            slow: [],
        );
    }

    private function nothingHeard(WhyNothingWasSaid $why): WhatTheOneLineSays
    {
        return new WhatTheOneLineSays(
            said: $why->met === '' ? self::WAITING : HowItStands::Unknown->saidOnTheScreen(),
            word: HowItStands::Unknown->saidInAWord(),
            tone: $why->met === '' ? Tone::Working->value : Tone::Unknown->value,
            counted: '',
            howMany: 0,
            worst: '',
            ago: AgoAsShown::live(),
            met: $why->met,
            remedy: $why->remedy,
            affected: [],
            stopped: [],
            slow: [],
        );
    }

    private function said(TheHealthSummary $summary, HowItStands $shown, AgoAsShown $ago, WhyNothingWasSaid $why): WhatTheOneLineSays
    {
        $affected = [];

        foreach ($summary as $item) {
            $affected[] = $this->item($item);
        }

        $stopped = [];
        $slow = [];

        foreach ($summary->stopped() as $row) {
            if ($row->how()->wantsAFix()) {
                $stopped[] = $this->stoppage($row);

                continue;
            }

            $slow[] = $this->stoppage($row);
        }

        return new WhatTheOneLineSays(
            said: $shown->saidOnTheScreen(),
            word: $shown->saidInAWord(),
            tone: $this->toneOf($shown),
            counted: $summary->wantingAttention() === 0 ? '' : $this->counted($shown),
            howMany: $summary->wantingAttention(),
            worst: $summary->worst(),
            ago: $ago,
            met: $why->met,
            remedy: $why->remedy,
            affected: $affected,
            stopped: $stopped,
            slow: $slow,
        );
    }

    /** The glyph a standing is drawn with: fine, something to look at, broken, or not known. */
    private function toneOf(HowItStands $shown): string
    {
        return match ($shown) {
            HowItStands::Healthy => Tone::Fine->value,
            HowItStands::Advisory, HowItStands::Degraded, HowItStands::Stopped, HowItStands::Unconfigured => Tone::Attention->value,
            HowItStands::Broken, HowItStands::Critical => Tone::Trouble->value,
            HowItStands::Unknown => Tone::Unknown->value,
        };
    }

    private function counted(HowItStands $shown): string
    {
        return match ($shown) {
            HowItStands::Advisory => 'health.summary.notes',
            HowItStands::Degraded, HowItStands::Broken, HowItStands::Critical => 'health.summary.wanting',
            HowItStands::Healthy, HowItStands::Stopped, HowItStands::Unconfigured, HowItStands::Unknown => 'health.summary.reported',
        };
    }

    private function item(AnAffectedItem $item): AnAffectedItemAsShown
    {
        $remedies = [];

        foreach ($item->remedies() as $remedy) {
            $remedies[] = $remedy->action();
        }

        $downstream = [];

        foreach ($item->downstream() as $said) {
            $downstream[] = $said;
        }

        return new AnAffectedItemAsShown(
            check: $item->check()->shown(),
            severity: $item->severity()->saidOnTheScreen(),
            summary: $item->summary(),
            meaning: $item->meaning(),
            remedies: $remedies,
            downstream: $downstream,
        );
    }

    /**
     * One stopped row, with how long it has been that way in words.
     *
     * A row standing for several items names their shared cause, and a trace
     * follows an item rather than a cause, so only a row standing for one is
     * given something to follow.
     */
    private function stoppage(AStoppage $row): AStoppageAsShown
    {
        $held = $row->heldFor();

        return new AStoppageAsShown(
            kindSaid: $row->how()->saidOnTheScreen(),
            name: $row->name(),
            items: $row->items(),
            blocking: $row->blocking(),
            heldSaid: $held->unit()->heldOnTheScreen(),
            heldCount: $held->howMany(),
            follows: $row->items() === 1 ? $row->name() : '',
        );
    }
}
