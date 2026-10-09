<?php

declare(strict_types=1);

namespace Lemonfiber\Native\Events;

use Native\Mobile\Events\Concerns\BroadcastsGlobally;

/**
 * The player's state moved, so whoever shows it reads it afresh.
 *
 * **It carries nothing, for the reason {@see TheLockMoved} carries nothing.**
 * Whatever arrives as an event could have been sent by anything able to send
 * one, so this says only *look again*: whoever hears it asks the device through
 * the bridge where the player stands, and a forged or replayed one can make the
 * app look again and nothing more.
 *
 * Global rather than a screen's own, because the player plays on over whichever
 * screen is underneath it, and in picture-in-picture over none of them.
 */
final readonly class ThePlayerMoved implements BroadcastsGlobally {}
