<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use Closure;

/**
 * Whether a stack wakes this phone through a distributor: off, on with what it was registered with, or off with a removal still owed to the stack.
 */
final readonly class HowThisPhoneIsWoken
{
    private function __construct(private ?ADistributor $through, private ?AWakeToRegister $registered, private ?APushName $owed) {}

    public static function off(): self
    {
        return new self(null, null, null);
    }

    /** The stack wakes this phone through `$through`, holding what was registered. */
    public static function on(ADistributor $through, AWakeToRegister $registered): self
    {
        return new self($through, $registered, null);
    }

    /** Off on this phone, and the stack still holds the endpoint named `$name` until it is told to remove it. */
    public static function owed(APushName $name): self
    {
        return new self(null, null, $name);
    }

    public function isOn(): bool
    {
        return $this->registered instanceof AWakeToRegister;
    }

    /** The distributor the stack wakes this phone through, which is none where waking is off. */
    public function through(): TheDistributors
    {
        return $this->through instanceof ADistributor && $this->registered instanceof AWakeToRegister ? TheDistributors::of($this->through) : TheDistributors::of();
    }

    /** Whether the stack still holds the endpoint and is owed its removal. */
    public function owesARemoval(): bool
    {
        return $this->owed instanceof APushName;
    }

    /**
     * @template TOff of object
     * @template TOn of object
     * @template TOwed of object
     *
     * @param  Closure(): TOff                             $off
     * @param  Closure(ADistributor, AWakeToRegister): TOn $on
     * @param  Closure(APushName): TOwed                   $owed
     * @return TOff|TOn|TOwed
     */
    public function either(Closure $off, Closure $on, Closure $owed): object
    {
        if ($this->through instanceof ADistributor && $this->registered instanceof AWakeToRegister) {
            return $on($this->through, $this->registered);
        }

        return $this->owed instanceof APushName ? $owed($this->owed) : $off();
    }
}
