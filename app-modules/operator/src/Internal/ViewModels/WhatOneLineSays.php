<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

use Modules\Operator\Internal\AGlyphForALine;

/**
 * One row of what a service wrote, flattened for a template to read.
 *
 * {@see \Modules\Kernel\Api\Said} answers its moment through a closure and
 * Blade has no way to call one, so
 * {@see \Modules\Operator\Internal\Presenters\HowALineReads} folds one once
 * per row — the argument {@see WhatOneStalledItemSays} makes, and the reason
 * that class exists.
 *
 * **A row is a line, or a run of decorative lines folded into one.** A fold
 * says how many lines it stands for and which fold it is, so a tap can open
 * it; an open fold is followed by the lines it holds, each a row of its own.
 *
 * **Whether there is a moment is a field of its own**, rather than being read
 * off an empty string. A line whose service wrote no timestamp and a line whose
 * timestamp this app dropped would look identical to a template branching on
 * emptiness, and the second is a bug the first would hide.
 *
 * `Internal` because it is a detail of how one surface reads a value; `E2`'s
 * promise is that anything here can be renamed without reading another module.
 */
final readonly class WhatOneLineSays
{
    /**
     * @param string                   $line          what the service wrote, exactly as it wrote it
     * @param string                   $streamSaid    the key for the error stream where the line came out of it, and empty otherwise
     * @param bool                     $worthNoticing whether a screen should let this one stand out
     * @param WhenALineWasWritten|null $written       when it was written, or null where the service gave no moment
     * @param AGlyphForALine|null      $glyph         the glyph the severity it declared earns, or null where it earns none
     */
    public function __construct(
        public string $line,
        public string $streamSaid,
        public bool $worthNoticing,
        public ?WhenALineWasWritten $written,
        public ?AGlyphForALine $glyph,
    ) {}
}
