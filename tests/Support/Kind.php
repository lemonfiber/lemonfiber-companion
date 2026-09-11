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
     * Vendor namespaces a module of this kind may never name.
     *
     * `Lemonfiber\Sdk` is absent from the adapter list on purpose: the sdk
     * module is an adapter and is the one place permitted to name it (N1-R16).
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
            self::Adapter => [],
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
        };
    }

    /** Whether a module of this kind renders, and so may hold mutable state. */
    public function renders(): bool
    {
        return $this === self::Surface;
    }
}
