<?php

declare(strict_types=1);

namespace Modules\Health\Internal;

use InvalidArgumentException;

use function json_decode;
use function json_encode;

use Modules\Kernel\Api\AnAffectedItem;
use Modules\Kernel\Api\AStoppage;
use Modules\Kernel\Api\Check;
use Modules\Kernel\Api\HowItStands;
use Modules\Kernel\Api\HowItStopped;
use Modules\Kernel\Api\Remedies;
use Modules\Kernel\Api\Remedy;
use Modules\Kernel\Api\Severity;
use Modules\Kernel\Api\Shape;
use Modules\Kernel\Api\TheHealthSummary;
use Modules\Kernel\Api\Unsealed;
use Modules\Kernel\Api\WhatFollowedFromIt;
use Modules\Kernel\Api\WhatStoppedMoving;

/**
 * A health summary as the phone writes it before sealing it, and reads it back after.
 *
 * **Written in {@see Shape::One}, field for field what the core sent**: the
 * word, how many want attention, the worst of them, every affected item and
 * every row of what stopped moving. Nothing is added and nothing is worked out,
 * so a summary read back is the summary that was heard.
 *
 * **Read back by the shape it says it was written in.** The match over
 * {@see Shape} is where a later layout has to be answered: a shape this build
 * writes and cannot read is a case the analyser refuses to leave out.
 *
 * **Anything that does not read is nothing.** A kept summary is sealed, so
 * what opens is what this phone wrote; one that still does not read as a
 * summary is let go of rather than repaired, because the stack can always be
 * asked again.
 */
final readonly class TheSummaryAsKept
{
    /**
     * The summary as a value to seal, or nothing where it cannot be written.
     *
     * A stack whose words are not valid text cannot be written as a kept
     * summary; it is shown as it was heard and kept nowhere.
     */
    public static function written(TheHealthSummary $summary): ?Unsealed
    {
        $affected = [];

        foreach ($summary as $item) {
            $affected[] = self::item($item);
        }

        $stopped = [];

        foreach ($summary->stopped() as $row) {
            $stopped[] = self::stoppage($row);
        }

        $written = json_encode([
            'standing' => $summary->standing()->value,
            'wanting' => $summary->wantingAttention(),
            'worst' => $summary->worst(),
            'affected' => $affected,
            'stopped' => $stopped,
        ]);

        return $written === false ? null : Unsealed::of($written);
    }

    /** The summary a value written in this shape holds, or nothing where it does not read as one. */
    public static function read(Shape $shape, Unsealed $value): ?TheHealthSummary
    {
        try {
            return match ($shape) {
                Shape::One => self::inShapeOne(WhatWasWritten::in(json_decode($value->inTheClear(), associative: true))),
            };
        } catch (InvalidArgumentException) {
            // A field missing or of the wrong type, and a kernel value
            // refusing what it was handed — a blank check, a count below
            // nothing — are the same answer: a summary that does not read.
            return null;
        }
    }

    private static function inShapeOne(WhatWasWritten $written): TheHealthSummary
    {
        $affected = [];

        foreach ($written->entries('affected') as $item) {
            $affected[] = self::itemIn($item);
        }

        $stopped = [];

        foreach ($written->entries('stopped') as $row) {
            $stopped[] = self::stoppageIn($row);
        }

        return TheHealthSummary::of(
            HowItStands::tryFrom($written->text('standing')) ?? throw KeptSummaryDoesNotRead::at('standing'),
            $written->number('wanting'),
            $written->text('worst'),
            WhatStoppedMoving::of(...$stopped),
            ...$affected,
        );
    }

    private static function itemIn(WhatWasWritten $item): AnAffectedItem
    {
        $remedies = [];

        foreach ($item->lines('remedies') as $action) {
            $remedies[] = Remedy::of($action);
        }

        return AnAffectedItem::of(
            Check::of($item->text('check')),
            Severity::tryFrom($item->text('severity')) ?? throw KeptSummaryDoesNotRead::at('severity'),
            $item->text('summary'),
            $item->text('meaning'),
            Remedies::of(...$remedies),
            WhatFollowedFromIt::of(...$item->lines('downstream')),
        );
    }

    private static function stoppageIn(WhatWasWritten $row): AStoppage
    {
        return AStoppage::of(
            HowItStopped::tryFrom($row->text('how')) ?? throw KeptSummaryDoesNotRead::at('how'),
            $row->text('name'),
            $row->number('items'),
            $row->text('blocking'),
            $row->number('held'),
        );
    }

    /** @return array<string, mixed> */
    private static function item(AnAffectedItem $item): array
    {
        $remedies = [];

        foreach ($item->remedies() as $remedy) {
            $remedies[] = $remedy->action();
        }

        $downstream = [];

        foreach ($item->downstream() as $said) {
            $downstream[] = $said;
        }

        return [
            'check' => $item->check()->shown(),
            'severity' => $item->severity()->value,
            'summary' => $item->summary(),
            'meaning' => $item->meaning(),
            'remedies' => $remedies,
            'downstream' => $downstream,
        ];
    }

    /** @return array<string, mixed> */
    private static function stoppage(AStoppage $row): array
    {
        return [
            'how' => $row->how()->value,
            'name' => $row->name(),
            'items' => $row->items(),
            'blocking' => $row->blocking(),
            'held' => $row->heldFor()->inSeconds(),
        ];
    }
}
