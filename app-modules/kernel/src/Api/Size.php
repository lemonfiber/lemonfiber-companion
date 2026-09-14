<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use Closure;

/**
 * How big something is, and whether anybody measured it.
 *
 * `D7-R3` wants the size shown before a request is submitted; `D7-R4` wants an
 * estimate labelled as one. Those read as two requirements and are one fact,
 * and a type carrying the number alone would let a screen answer the first and
 * fail the second without anybody noticing — the number renders either way.
 *
 * So there is no `bytes()`. The only way to reach the figure is to say what
 * happens in both cases, and the guessed arm is handed the figure *and* the
 * obligation to say so. A screen rendering a guess as a measurement has to have
 * been handed the distinction and dropped it, which is visible in review in a
 * way a missing call is not — the argument {@see Reading} makes about an age.
 *
 * **A third arm for *nobody knows*.** The wire carries no estimate at all for a
 * request nothing has sized yet, and that is neither a measurement nor a guess.
 * Folding it into a guess of zero would put *0 bytes* on a screen beside a
 * request for a nineteen-season procedural, which is worse than saying nothing.
 */
final readonly class Size
{
    private function __construct(private ?int $bytes, private bool $measured) {}

    /** Somebody measured it, so the figure stands on its own. */
    public static function measured(int $bytes): self
    {
        return new self($bytes, measured: true);
    }

    /** It was worked out rather than measured, and must be said to be (`D7-R4`). */
    public static function guessedAt(int $bytes): self
    {
        return new self($bytes, measured: false);
    }

    /**
     * Nothing has sized it.
     *
     * Not zero, and not a guess of zero. An operator deciding whether to let
     * something onto their disk is entitled to *we do not know* — it is a real
     * answer and the one that sends them to look rather than to approve.
     */
    public static function unknown(): self
    {
        return new self(null, measured: false);
    }

    /**
     * Say what happens for each, and get back what you built.
     *
     * @template TMeasured of object
     * @template TGuessed of object
     * @template TUnknown of object
     *
     * @param  Closure(int): TMeasured  $measured
     * @param  Closure(int): TGuessed  $guessed
     * @param  Closure(): TUnknown  $unknown
     * @return TMeasured|TGuessed|TUnknown
     */
    public function either(Closure $measured, Closure $guessed, Closure $unknown): object
    {
        // The arms that carry a figure are read first, as every fold here does:
        // a fall-through is how a branch becomes the one nobody tested, and the
        // one that would become it here is *nobody knows* — which would render
        // a request nobody has sized as one somebody measured.
        return match (true) {
            $this->bytes === null => $unknown(),
            $this->measured => $measured($this->bytes),
            default => $guessed($this->bytes),
        };
    }
}
