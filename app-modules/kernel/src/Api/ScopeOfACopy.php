<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use Closure;

/**
 * How much a copy covers: the whole stack, one service, or an existing setup.
 *
 * Three different undertakings, and an operator agrees to one of them. So the
 * scope is carried as the stack named it rather than as a word, and every
 * screen that states it has to say which of the three it is.
 */
final readonly class ScopeOfACopy
{
    private function __construct(
        private ?ServiceId $service = null,
        private ?AnExistingSetup $existing = null,
    ) {}

    /** Every service's configuration, lemonfiber's own, and the stack. */
    public static function theWholeStack(): self
    {
        return new self();
    }

    /** One service's configuration and nothing else. */
    public static function oneService(ServiceId $service): self
    {
        return new self(service: $service);
    }

    /** A setup lemonfiber does not manage, read from where it keeps its own. */
    public static function anExistingSetup(AnExistingSetup $existing): self
    {
        return new self(existing: $existing);
    }

    /**
     * Say what happens for each scope, and get back what you built.
     *
     * Every arm required: a scope a screen forgot is a scope it would state
     * as something else.
     *
     * @template TWhole of object
     * @template TOne of object
     * @template TExisting of object
     *
     * @param Closure(): TWhole                   $wholeStack
     * @param Closure(ServiceId): TOne            $oneService
     * @param Closure(AnExistingSetup): TExisting $existing
     *
     * @return TWhole|TOne|TExisting
     */
    public function either(Closure $wholeStack, Closure $oneService, Closure $existing): object
    {
        return match (true) {
            $this->service instanceof ServiceId => $oneService($this->service),
            $this->existing instanceof AnExistingSetup => $existing($this->existing),
            default => $wholeStack(),
        };
    }
}
