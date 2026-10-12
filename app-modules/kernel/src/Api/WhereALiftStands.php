<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use Closure;

use function trim;

/**
 * Where a temporary lift of the limits stands against the clock: none, in force with this long left, or run out this long ago.
 */
final readonly class WhereALiftStands
{
    private function __construct(private ?int $leftFor, private ?int $ranOutAgo, private string $says) {}

    /** No lift was asked for. */
    public static function none(): self
    {
        return new self(null, null, '');
    }

    /** In force, with this many seconds left, and what it amounts to in the stack's words. */
    public static function inForce(int $seconds, string $says): self
    {
        return new self(self::counted($seconds), null, trim($says));
    }

    /** It ran out this many seconds ago, and what that amounts to in the stack's words. */
    public static function expired(int $seconds, string $says): self
    {
        return new self(null, self::counted($seconds), trim($says));
    }

    /** What the lift amounts to in the stack's words, empty where it gave none. */
    public function says(): string
    {
        return $this->says;
    }

    /**
     * Say what happens in each case, and get back what you built.
     *
     * @template TNone of object
     * @template TInForce of object
     * @template TExpired of object
     *
     * @param Closure(): TNone         $none
     * @param Closure(int): TInForce  $inForce
     * @param Closure(int): TExpired  $expired
     *
     * @return TNone|TInForce|TExpired
     */
    public function either(Closure $none, Closure $inForce, Closure $expired): object
    {
        return match (true) {
            $this->leftFor !== null => $inForce($this->leftFor),
            $this->ranOutAgo !== null => $expired($this->ranOutAgo),
            default => $none(),
        };
    }

    private static function counted(int $seconds): int
    {
        if ($seconds < 0) {
            throw LineSaysNothing::negative('respite.seconds', $seconds);
        }

        return $seconds;
    }
}
