<?php

declare(strict_types=1);

namespace Tests\Support;

/**
 * What a module is, and therefore what it may reach for.
 *
 * Declared by each module in its own manifest under `extra.lemonfiber.kind`,
 * and read back here to generate that module's boundary rules. The point of
 * deriving the rules rather than writing them out is that a module added
 * tomorrow is governed the moment it exists — nobody has to remember to add a
 * test for it, and a forgotten rule is indistinguishable from a permitted one.
 */
enum Kind: string
{
    /** Ports, values and outcomes. The shared language, and nothing else. */
    case Kernel = 'kernel';

    /** Domain logic, pure by construction because it may reach nothing. */
    case Capability = 'capability';

    /** The EDGE component vocabulary and the theme tokens. */
    case Design = 'design';

    /** Navigation and screen composition. Holds the mutable component shapes. */
    case Surface = 'surface';

    /** The one implementation that knows a specific outside thing. */
    case Adapter = 'adapter';

    /**
     * Takes the place of an adapter while somebody is working on the app.
     *
     * Its own kind because it fits none of the five above and the difference
     * matters in every direction. It names other adapters, which an adapter may
     * not; it names the outside vocabulary, which only the one adapter that
     * owns each may; and it is absent from a release, which none of the others
     * is. Calling it an adapter would have been the easy answer and would have
     * meant either widening what every adapter may reach, or a pile of
     * exceptions carrying this module's name.
     *
     * **What earns it those permissions is that nothing ships it.** A module of
     * this kind is installed under `require-dev`, so its provider is not
     * discovered in a release and its classes are not in the bundle. That
     * absence is the whole guarantee: there is no setting to get wrong, because
     * there is nothing there to switch on.
     */
    case StandIn = 'stand-in';

    /**
     * Vendor namespaces a module of this kind may never name.
     *
     * `Lemonfiber\Sdk` is absent from the adapter list on purpose: the sdk
     * module is an adapter and is the one place permitted to name it.
     * That single exception is asserted separately, by name, so it cannot be
     * widened by adding another adapter.
     *
     * @return list<string>
     */
    public function forbiddenVendors(): array
    {
        return match ($this) {
            // The domain does not know a framework exists (A7), does not know
            // the platform exists, and does not know how it is reached.
            self::Kernel, self::Capability => [
                'Illuminate',
                'Native',
                'Lemonfiber\Sdk',
                'Saloon',
                'GuzzleHttp',
                'Symfony',
                'Carbon',
            ],
            // Renders, so it knows the platform. Never reaches a stack.
            self::Design, self::Surface => [
                'Lemonfiber\Sdk',
                'Saloon',
                'GuzzleHttp',
            ],
            // Knows exactly one outside thing, which its own manifest declares.
            //
            // A stand-in names the outside vocabulary for the opposite reason
            // an adapter does — to put something in front of it rather than to
            // reach it — and what actually keeps the socket shut is narrower:
            // exactly one file may build a transport, whatever else names one.
            self::Adapter, self::StandIn => [],
        };
    }

    /**
     * Module kinds a module of this kind may depend on.
     *
     * @return list<self>
     */
    public function mayDependOn(): array
    {
        return match ($this) {
            self::Kernel => [],
            self::Capability, self::Adapter, self::Design => [self::Kernel],
            self::Surface => [self::Kernel, self::Design, self::Capability],
            // The adapters too, because standing in for one means holding the
            // real one: the stand-in asks it for what it would have built and
            // replaces only the part that reaches outside. Building its own
            // instead would put a second constructor for the outside thing in
            // this repository, where exactly one file may build a transport.
            self::StandIn => [self::Kernel, self::Adapter],
        };
    }

    /**
     * The coverage floor a module of this kind usually carries.
     *
     * Named in G7's failure message rather than applied as a default. The
     * difference is the whole point: a suggestion has to be typed into the
     * module's own manifest by somebody who looked at it, and a default is a
     * number that arrives without anyone deciding.
     */
    public function conventionalCoverageFloor(): int
    {
        // Every kind, for now. The run has been held to 100% since the first
        // commit, so twelve floors of 100 are the same bar written twelve
        // times — what changes is that lowering one is local and visible
        // instead of dropping the number for everybody.
        return 100;
    }

    /** The mutation floor a module of this kind usually carries. */
    public function conventionalMutationFloor(): int
    {
        return match ($this) {
            // Where the decisions are, and therefore where a surviving mutant
            // means a test that asserts nothing.
            self::Kernel, self::Capability, self::Surface => 100,
            // A component holds state and an adapter forwards a call. Mutating
            // either measures the fake rather than the application, which is a
            // number that looks like rigour and is not. A stand-in is a fake by
            // construction, so the argument is the same one twice over.
            self::Design, self::Adapter, self::StandIn => 0,
        };
    }

    /** Whether a module of this kind renders, and so may hold mutable state. */
    public function renders(): bool
    {
        return $this === self::Surface;
    }
}
