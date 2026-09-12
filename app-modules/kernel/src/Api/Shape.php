<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * The version of the layout retained state was written in.
 *
 * `N1-R32` asks that anything the app keeps between launches carries this, and
 * it is the requirement that cannot wait: `N1-R33` has the app migrate older
 * state or discard it rather than interpret it as current, and neither is
 * possible for state that never recorded which shape it was. **State written
 * today without this marker is unmigratable forever**, so the marker ships
 * before the machinery that will read it.
 *
 * **Not the app's version, and not the wire's.** An app version moves for
 * reasons that have nothing to do with what is on disk — a copy change ships a
 * new build and the retained state is byte-identical — so keying migration off
 * it would migrate on every release and prove nothing. {@see WireVersion} is the
 * contract with the stack, a different party at a different cadence: a stack can
 * change what it sends without this app changing what it keeps, and the reverse.
 *
 * So this moves when, and only when, the layout of something retained changes.
 *
 * `current()` is an accessor rather than a case, because a case named `Current`
 * renames itself the day the next one arrives — and every stored value that said
 * `current` then means the wrong shape.
 *
 * **There is deliberately no `isCurrent()` and no comparison here yet.** With one
 * case every such method is a branch nothing can reach and no test can kill, and
 * the analyser says so. They arrive with the second case, which is also the
 * first commit that has a real migration to run and something to test it
 * against — see the note in `ShapeTest` on what that commit has to add.
 */
enum Shape: int
{
    /** The first layout: a paired stack, its address and its fingerprint. */
    case One = 1;

    /** The shape this build writes into anything it retains. */
    public static function current(): self
    {
        return self::One;
    }
}
