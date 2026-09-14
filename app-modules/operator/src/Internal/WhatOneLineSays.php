<?php

declare(strict_types=1);

namespace Modules\Operator\Internal;

use Modules\Kernel\Api\Said;

/**
 * One line a service wrote, flattened for a template to read.
 *
 * {@see Said} answers its moment through a closure and Blade has no way to call
 * one, so the fold happens once per row here — the argument
 * {@see WhatOneStalledItemSays} makes, and the reason that class exists.
 *
 * **The moment is carried as the service stated it.** Not reformatted, not put
 * in the phone's timezone: a log line's timestamp is for matching it against
 * the lines around it, which are all in the service's own frame, and two
 * operators in different places must not disagree about when something happened
 * on the same machine.
 *
 * **Whether there is one is a field of its own**, rather than being read off an
 * empty string. A line whose service wrote no timestamp and a line whose
 * timestamp this app dropped would look identical to a template branching on
 * emptiness, and the second is a bug the first would hide.
 *
 * `Internal` because it is a detail of how one surface reads a value; `E2`'s
 * promise is that anything here can be renamed without reading another module.
 */
final readonly class WhatOneLineSays
{
    /**
     * @param string $line          what the service wrote, exactly as it wrote it
     * @param string $streamSaid    the key for which of its two mouths it came out of
     * @param bool   $worthNoticing whether a screen should let this one stand out
     * @param string $at            the moment, in the service's own words
     * @param bool   $hasAMoment    whether the service gave one at all
     */
    private function __construct(
        public string $line,
        public string $streamSaid,
        public bool $worthNoticing,
        public string $at,
        public bool $hasAMoment,
    ) {}

    /** Fold one line into the fields a row needs. */
    public static function in(Said $said): self
    {
        $stream = $said->stream();

        return $said->when(
            then: static fn(string $when): self => new self(
                line: $said->line(),
                streamSaid: $stream->saidOnTheScreen(),
                worthNoticing: $stream->worthNoticing(),
                at: $when,
                hasAMoment: true,
            ),
            unstated: static fn(): self => new self(
                line: $said->line(),
                streamSaid: $stream->saidOnTheScreen(),
                worthNoticing: $stream->worthNoticing(),
                at: '',
                hasAMoment: false,
            ),
        );
    }
}
