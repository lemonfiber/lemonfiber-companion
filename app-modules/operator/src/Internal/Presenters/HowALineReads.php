<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\Presenters;

use Modules\Kernel\Api\AMomentAsWritten;
use Modules\Kernel\Api\HowSeriousALineIs;
use Modules\Kernel\Api\Instant;
use Modules\Kernel\Api\Said;
use Modules\Kernel\Api\Zone;
use Modules\Operator\Internal\AGlyphForALine;
use Modules\Operator\Internal\AsText;
use Modules\Operator\Internal\ViewModels\WhatOneLineSays;

/**
 * What one line a service wrote comes to, as the fields a row reads.
 *
 * **The time is the phone's.** A log line is read by somebody holding the
 * phone, who is matching it against when things happened to them, so its
 * moment is shown on their own clock and to the second. The moment exactly
 * as the service wrote it is kept beside it for a screen reader, so nothing
 * the service said about when is lost.
 *
 * **A moment that cannot be read is shown as written.** A service may put
 * anything where a timestamp goes, and guessing a time from it would be worse
 * than showing what it wrote.
 *
 * **Only the error stream is marked.** Ordinary output is what nearly every line
 * is, and a mark on every row says nothing.
 *
 * **A line's glyph is its own word for itself.** An error — and one the service
 * did not survive — is drawn with trouble's glyph, a warning with attention's,
 * from the severity the core read off the line. Every other severity, and a
 * line the core could not classify, is drawn without one: a glyph on an info
 * line would teach the eye to skip glyphs.
 */
final readonly class HowALineReads
{
    /** Fold one line into the fields a row needs, its time read on that zone's clock. */
    public function in(Said $said, Zone $zone): WhatOneLineSays
    {
        $stream = $said->stream();
        $noticed = $stream->worthNoticing();
        $marked = $noticed ? $stream->saidOnTheScreen() : '';
        $level = $said->level(
            declared: static fn(HowSeriousALineIs $level): AGlyphForALine => AGlyphForALine::for($level),
            undeclared: static fn(): AGlyphForALine => AGlyphForALine::none(),
        );

        return $said->when(
            then: static fn(string $when): WhatOneLineSays => new WhatOneLineSays(
                line: $said->line(),
                streamSaid: $marked,
                worthNoticing: $noticed,
                at: AMomentAsWritten::of($when)->read(
                    read: static fn(Instant $moment): AsText => AsText::of($zone->timeOfDayAt($moment)->shown()),
                    unreadable: static fn(): AsText => AsText::of($when),
                )->said,
                atInFull: $when,
                hasAMoment: true,
                tone: $level->tone,
                levelSaid: $level->said,
                isAnError: $level->isAnError,
            ),
            unstated: static fn(): WhatOneLineSays => new WhatOneLineSays(
                line: $said->line(),
                streamSaid: $marked,
                worthNoticing: $noticed,
                at: '',
                atInFull: '',
                hasAMoment: false,
                tone: $level->tone,
                levelSaid: $level->said,
                isAnError: $level->isAnError,
            ),
        );
    }

    /** A run of decorative lines, folded into one row that says how many. */
    public function folding(int $howMany, int $fold, bool $isOpen): WhatOneLineSays
    {
        return new WhatOneLineSays(
            line: '',
            streamSaid: '',
            worthNoticing: false,
            at: '',
            atInFull: '',
            hasAMoment: false,
            folded: $howMany,
            fold: $fold,
            isOpen: $isOpen,
        );
    }
}
