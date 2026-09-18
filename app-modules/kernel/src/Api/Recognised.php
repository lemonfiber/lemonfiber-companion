<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use Closure;

/**
 * Whether the machine that answered is the one the app was introduced to.
 *
 * The requirement is unusually specific about the shape of this answer, and the
 * specificity is the requirement: a connection presenting a different
 * certificate is **refused rather than warned about**. A warning is a dialog
 * with a way past it, and the way past it is the thing an attacker needs. So
 * there is no arm here that means "not the paired stack, but carry on" — the
 * two arms are the machine we know and a stranger, and the stranger arm carries
 * an {@see Obstacle} rather than a flag somebody may ignore.
 *
 * The obstacle is `StackIsNotTheOnePaired`, which already exists with everything
 * the requirement asks for attached to it: `Critical` severity, a code an
 * operator can search for, a sentence saying this machine is not the one the app
 * was introduced to, and a remedy offering re-pairing. This type is only the
 * comparison; the reporting was already built.
 *
 * **Why a sum type and not a `bool`.** `true` and `false` are the same shape, so
 * the call site that inverts them compiles and ships. Here the two arms take
 * different arguments — the stranger arm is handed the obstacle, the paired arm
 * is handed nothing — and there is no way to read one as the other.
 */
final readonly class Recognised
{
    private function __construct(private ?Obstacle $refused) {}

    /** The certificate matched the one pinned at pairing. */
    public static function asThePairedStack(): self
    {
        return new self(null);
    }

    /**
     * It did not, so this is not that machine.
     *
     * Takes no argument: there is exactly one obstacle this can be, and letting
     * a caller choose it would let a caller choose a gentler one.
     */
    public static function asAStranger(): self
    {
        return new self(Obstacle::StackIsNotTheOnePaired);
    }

    /**
     * @template TPaired of object
     * @template TStranger of object
     *
     * @param  Closure(): TPaired  $paired
     * @param  Closure(Obstacle): TStranger  $stranger
     * @return TPaired|TStranger
     */
    public function either(Closure $paired, Closure $stranger): object
    {
        return $this->refused instanceof Obstacle
            ? $stranger($this->refused)
            : $paired();
    }
}
