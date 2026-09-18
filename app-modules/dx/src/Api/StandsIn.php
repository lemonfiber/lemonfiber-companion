<?php

declare(strict_types=1);

namespace Modules\Dx\Api;

/**
 * Something that takes the place of a real thing while the app is being worked on.
 *
 * One place is asked for where local-only affordances live, and for that
 * place to admit a new one without any release artefact changing. This is the
 * shape that makes the second half true: a new affordance is a class in this
 * module implementing this, and nothing outside `app-modules/dx` is edited to
 * add it.
 *
 * **Nothing implements it yet, and the module is deliberately inert until
 * something does.** A registry holding nothing, a provider binding nothing and
 * a loop with no iterations are three pieces of code no test can reach, and the
 * coverage floor is right to refuse them — a scaffold is a claim that work has
 * started rather than work that has. *Operate* is not met until a stack can
 * actually be stood in for.
 *
 * **It names a port and answers with one.** Every seam this application has is
 * a port in `kernel`, so standing in for something is always the same move —
 * bind the port to something else. A stand-in that took the container and did
 * as it liked would be able to reach past the ports, which is the one thing
 * that would make what is being looked at differ from what ships.
 *
 * **What it is not.** Not a mode, not a setting, not a flag the app reads.
 * A released build that can run against one of these is refused, and the
 * way that is kept is that this module is a development dependency: a release
 * installs without it and the classes are not in the bundle at all. There is
 * nothing to switch off because there is nothing there.
 *
 * `TPort` is covariant because it only ever appears in a return position —
 * `insteadOf()` answers with its name and `which()` answers with one. That
 * makes a `StandsIn<Reaching>` usable wherever a `StandsIn<object>` is wanted,
 * which is what a registry holding several of them needs: they replace
 * different ports and the only thing true of all of them is that each replaces
 * something.
 *
 * @template-covariant TPort of object
 */
interface StandsIn
{
    /**
     * The port it takes the place of.
     *
     * @return class-string<TPort>
     */
    public function insteadOf(): string;

    /**
     * What answers in that port's place.
     *
     * Built per call rather than held, because the ports it replaces are bound
     * that way for reasons that still apply — a client is built for one stack
     * and a store is a handle to something outside this process. A stand-in
     * that was a singleton where the real one is not would be a difference
     * between what is being looked at and what ships, which is the whole thing
     * this module exists not to be.
     *
     * @return TPort
     */
    public function which(): object;
}
