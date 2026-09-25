<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\Presenters;

use Modules\Health\Api\WhatWasHeardSoFar;
use Modules\Kernel\Api\AnAffectedItem;
use Modules\Kernel\Api\HowItStands;
use Modules\Kernel\Api\HowLongAgo;
use Modules\Kernel\Api\Instant;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\TheHealthSummary;
use Modules\Operator\Internal\ViewModels\AnAffectedItemAsShown;
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
            none: fn(): WhatTheOneLineSays => $this->nothingHeard($why, $heard->isListening()),
            current: fn(TheHealthSummary $summary): WhatTheOneLineSays
                => $this->said($summary, $summary->standing(), AgoAsShown::live(), $why, $heard->isListening()),
            asOf: fn(TheHealthSummary $summary, Instant $at): WhatTheOneLineSays
                => $this->said($summary, HowItStands::Unknown, AgoAsShown::from(HowLongAgo::since($at, $now), $at, $now), $why, $heard->isListening()),
        );
    }

    private function nothingHeard(WhyNothingWasSaid $why, bool $listening): WhatTheOneLineSays
    {
        return new WhatTheOneLineSays(
            said: $why->met === '' ? self::WAITING : HowItStands::Unknown->saidOnTheScreen(),
            counted: '',
            howMany: 0,
            worst: '',
            ago: AgoAsShown::live(),
            met: $why->met,
            remedy: $why->remedy,
            listening: $listening,
            affected: [],
        );
    }

    private function said(TheHealthSummary $summary, HowItStands $shown, AgoAsShown $ago, WhyNothingWasSaid $why, bool $listening): WhatTheOneLineSays
    {
        $affected = [];

        foreach ($summary as $item) {
            $affected[] = $this->item($item);
        }

        return new WhatTheOneLineSays(
            said: $shown->saidOnTheScreen(),
            counted: $summary->wantingAttention() === 0 ? '' : $this->counted($shown),
            howMany: $summary->wantingAttention(),
            worst: $summary->worst(),
            ago: $ago,
            met: $why->met,
            remedy: $why->remedy,
            listening: $listening,
            affected: $affected,
        );
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
}
