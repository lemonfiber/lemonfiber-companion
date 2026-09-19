<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use Closure;

/**
 * What the core says one member may watch.
 *
 * The shelf is the core's answer and this carries it. Which libraries a member
 * reaches, what their age limit allows and what they are entitled to are
 * decided where those live, and the list that arrives here is already the
 * result — so nothing on this side filters it, and a player built on it holds
 * no second copy of any of the three.
 *
 * **Three answers, and the middle one is the reason this is not two.** A shelf
 * that came back empty and a shelf that could not be read look identical to a
 * screen drawing a list, and say opposite things to the person reading it: one
 * is *you have nothing here* and the other is *your library is out of reach*.
 * Drawing the second as the first tells somebody their collection is gone.
 *
 * **Out of reach is the core's word, never this app's.** Whether the media
 * server can be answered for is a question about the route between two
 * machines, and an app deciding it by looking at an address decides it wrongly
 * the first time that route is not the one it assumed.
 */
final readonly class WhatTheyMayWatch
{
    private function __construct(
        private ?Shelf $shelf,
        private Sentences $said,
        private ?Obstacle $why,
    ) {}

    /**
     * The core answered, and this is the shelf.
     *
     * An empty one is an ordinary answer rather than a missing one, and a
     * screen says so in as many words rather than by showing nothing at all.
     */
    public static function told(Shelf $shelf): self
    {
        return new self($shelf, Sentences::none(), null);
    }

    /**
     * The core answered and the shelf was not its to give.
     *
     * The media server did not hand it over, so there is no list — not an
     * empty one. What the core said about that is carried as it said it, since
     * the reason belongs to whoever could not reach what.
     */
    public static function outOfReach(Sentences $said): self
    {
        return new self(null, $said, null);
    }

    /** The stack would not say, and this is what stood in the way. */
    public static function refused(Obstacle $why): self
    {
        return new self(null, Sentences::none(), $why);
    }

    /**
     * Say what happens in each case, and get back what you built.
     *
     * Three arms rather than a flag beside two, for {@see Resumed}'s reason: a
     * check-then-get pair is a pair somebody forgets, and the one forgotten
     * here draws an empty shelf over a library nobody could reach.
     *
     * @template TTold of object
     * @template TOutOfReach of object
     * @template TRefused of object
     *
     * @param  Closure(Shelf): TTold  $told
     * @param  Closure(Sentences): TOutOfReach  $outOfReach
     * @param  Closure(Obstacle): TRefused  $refused
     * @return TTold|TOutOfReach|TRefused
     */
    public function either(Closure $told, Closure $outOfReach, Closure $refused): object
    {
        if ($this->why instanceof Obstacle) {
            return $refused($this->why);
        }

        // Absent rather than empty, so the two are told apart by the type
        // instead of by a flag beside it: an empty shelf is a `Shelf` with
        // nothing on it, and a library out of reach has no shelf at all.
        return $this->shelf instanceof Shelf ? $told($this->shelf) : $outOfReach($this->said);
    }
}
