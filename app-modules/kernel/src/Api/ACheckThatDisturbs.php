<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use Closure;

use function trim;

/**
 * A check the stack left unverified because running it disturbs something, with what it disturbs and how a diagnosis runs it.
 *
 * Every word is the stack's: what it disturbs, the check or group a diagnosis
 * is narrowed to, and how long the disturbance is bounded to where the stack
 * bounds it. Two arms for the bound rather than a number that may be absent,
 * so a screen says the stack gave none rather than drawing a zero.
 */
final readonly class ACheckThatDisturbs
{
    private function __construct(
        private string $disturbs,
        private string $only,
        private int $seconds,
        private bool $bounded,
    ) {}

    /** It disturbs this, bounded to this many seconds; a blank word or a bound below none is refused. */
    public static function boundedTo(string $disturbs, string $only, int $seconds): self
    {
        if ($seconds < 0) {
            throw ADisturbanceSaysNothing::below($seconds);
        }

        return new self(self::said($disturbs, 'disturbs'), self::said($only, 'only'), $seconds, bounded: true);
    }

    /** It disturbs this, and the stack gives no bound; a blank word is refused. */
    public static function unbounded(string $disturbs, string $only): self
    {
        return new self(self::said($disturbs, 'disturbs'), self::said($only, 'only'), 0, bounded: false);
    }

    /** What running it disturbs, in the stack's words. */
    public function disturbs(): string
    {
        return $this->disturbs;
    }

    /** The check or group a diagnosis is narrowed to, to run it. */
    public function narrowedTo(): string
    {
        return $this->only;
    }

    /**
     * Say what happens with a bound and without one, and get back what you built.
     *
     * @template TBounded of object
     * @template TUnbounded of object
     *
     * @param Closure(int): TBounded $bounded   given the bound in whole seconds
     * @param Closure(): TUnbounded  $unbounded
     *
     * @return TBounded|TUnbounded
     */
    public function either(Closure $bounded, Closure $unbounded): object
    {
        return $this->bounded ? $bounded($this->seconds) : $unbounded();
    }

    private static function said(string $word, string $field): string
    {
        $trimmed = trim($word);

        if ($trimmed === '') {
            throw ADisturbanceSaysNothing::about($field);
        }

        return $trimmed;
    }
}
