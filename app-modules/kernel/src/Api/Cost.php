<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function sprintf;

/**
 * What applying a change costs, which decides whether anybody is asked first.
 *
 * The core decides this and the app carries it. A surface that worked out for
 * itself which settings are consequential would hold a second copy of a
 * judgement the core already makes — and would be wrong about a setting the
 * core learned something new about, silently, until somebody lost a library.
 */
enum Cost: string
{
    /**
     * A restart of the services it affects, and nothing else moves.
     */
    case Cheap = 'cheap';

    /**
     * Said and confirmed before it is applied: it may move data, invalidate
     * library paths, or take a service away.
     */
    case Consequential = 'consequential';

    public function saidOnTheScreen(): string
    {
        return sprintf('config.cost.%s', $this->value);
    }

    /**
     * Whether the operator is asked before this is written.
     *
     * A method rather than a comparison at each call site. `$cost ===
     * Cost::Consequential` reads as a fact about an enum; this reads as the
     * question the screen is actually asking, and there is one place to
     * change if a third cost is ever published.
     */
    public function mustBeAgreedFirst(): bool
    {
        return match ($this) {
            self::Cheap => false,
            self::Consequential => true,
        };
    }
}
