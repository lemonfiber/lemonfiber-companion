<?php

declare(strict_types=1);

namespace Modules\Operator\Internal;

use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\StackId;

use function sprintf;

/**
 * What the app found when it opened, flattened for a template to read.
 *
 * {@see \Modules\Kernel\Api\Launch::either()} answers with an object and
 * requires all four arms, which is `N1-R37` expressed as a signature: a launch
 * with no network, one that cannot reach the stack and one where the app is
 * locked must be told apart, and an optional arm would be a default — the place
 * two of the four quietly become the same answer.
 *
 * That is right for a value and impossible for Blade, which has no `either()`,
 * so the screen folds the launch into this once and the template reads fields.
 *
 * **All four are carried, not just the one the screen draws today.** Only
 * `isLocked` is rendered so far, and the temptation is to fold the other three
 * into "not locked" and be done. Doing that would rebuild the collapse
 * `N1-R37` exists to prevent, one layer down and out of sight of the type that
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
     * @param string $opensOn   which stack the launch is about, or empty where none
     */
    private function __construct(
        public bool $isLocked,
        public bool $isPaired,
        public string $met,
        public string $opensOn,
    ) {}

    /** The device would not let the operator in, and nothing was tried (`N4-R19`). */
    public static function locked(): self
    {
        return new self(isLocked: true, isPaired: false, met: '', opensOn: '');
    }

    /** No machine is paired, which is a first run rather than a fault (`N1-R35`). */
    public static function unpaired(): self
    {
        return new self(isLocked: false, isPaired: false, met: '', opensOn: '');
    }

    /**
     * Something stood between the app and the machine it is paired with.
     *
     * The key comes off the obstacle rather than being spelled, which is the
     * derivation every screen in this application uses — and it is why an
     * obstacle gaining a seventh case needs no edit here.
     */
    public static function blockedBy(Obstacle $why): self
    {
        return new self(
            isLocked: false,
            isPaired: true,
            met: sprintf('connection.%s', $why->value),
            opensOn: '',
        );
    }

    /** A machine is paired and ready to be asked (`N1-R36`). */
    public static function readyFor(StackId $stack): self
    {
        return new self(isLocked: false, isPaired: true, met: '', opensOn: $stack->stored());
    }
}
