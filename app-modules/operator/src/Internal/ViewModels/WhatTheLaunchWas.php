<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/**
 * What the app found when it opened, flattened for a template to read.
 *
 * {@see \Modules\Kernel\Api\Launch::either()} answers with an object and
 * requires all four arms, which is the rule expressed as a signature: a launch
 * with no network, one that cannot reach the stack and one where the app is
 * locked must be told apart, and an optional arm would be a default — the place
 * two of the four quietly become the same answer.
 *
 * That is right for a value and impossible for Blade, which has no `either()`,
 * so {@see \Modules\Operator\Internal\Presenters\HowTheLaunchReads} folds the
 * launch into this once and the template reads fields.
 *
 * **All four are carried, not just the one the screen draws today.** Only
 * `isLocked` is rendered so far, and the temptation is to fold the other three
 * into "not locked" and be done. Doing that would rebuild the collapse
 * the rule exists to prevent, one layer down and out of sight of the type that
 * refuses it — and the next screen to want *why the stack could not be reached*
 * would find the answer already discarded.
 *
 * `Internal` because it is a detail of how this surface reads one value, and
 * `E2`'s promise is that anything here can be renamed without reading another
 * module.
 */
final readonly class WhatTheLaunchWas
{
    /**
     * @param bool   $isLocked  whether the app is shut until the operator proves who they are
     * @param bool   $isPaired  whether this device knows any machine at all
     * @param string $met       the key for what stood in the way, or empty where nothing did
     * @param string $remedy    the key for what to do about it, or empty where nothing did
     * @param string $opensOn   which stack the launch is about, or empty where none
     */
    public function __construct(
        public bool $isLocked,
        public bool $isPaired,
        public string $met,
        public string $remedy,
        public string $opensOn,
    ) {}
}
