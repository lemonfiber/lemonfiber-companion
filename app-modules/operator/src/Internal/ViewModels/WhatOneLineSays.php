<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/**
 * One line a service wrote, flattened for a template to read.
 *
 * {@see \Modules\Kernel\Api\Said} answers its moment through a closure and
 * Blade has no way to call one, so
 * {@see \Modules\Operator\Internal\Presenters\HowALineReads} folds one once
 * per row — the argument {@see WhatOneStalledItemSays} makes, and the reason
 * that class exists.
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
    public function __construct(
        public string $line,
        public string $streamSaid,
        public bool $worthNoticing,
        public string $at,
        public bool $hasAMoment,
    ) {}
}
