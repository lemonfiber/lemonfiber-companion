<?php

declare(strict_types=1);

namespace Tests\Support\Fakes;

use function array_key_exists;

use Modules\Kernel\Api\AGrant;
use Modules\Kernel\Api\Forgotten;
use Modules\Kernel\Api\KeepingTheGrant;
use Modules\Kernel\Api\Kept;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\TheGrantHeld;
use Modules\Kernel\Api\TheGrantIsFor;
use Modules\Kernel\Api\WhySessionCannotBeKept;

/**
 * This device's grant on each stack, held in memory.
 *
 * `refusing()` is a device whose store will not open: nothing is kept, and
 * nothing is read back.
 */
final class GrantsKeptInMemory implements KeepingTheGrant
{
    /** @var array<string, array{TheGrantIsFor, AGrant}> */
    private array $grants = [];

    private function __construct(private readonly bool $keeps) {}

    public static function working(): self
    {
        return new self(keeps: true);
    }

    public static function refusing(): self
    {
        return new self(keeps: false);
    }

    public function theGrantOn(StackId $stack, TheGrantIsFor $for): TheGrantHeld
    {
        if (! array_key_exists($stack->stored(), $this->grants)) {
            return TheGrantHeld::none();
        }

        [$keptFor, $grant] = $this->grants[$stack->stored()];

        return $keptFor->is($for) ? TheGrantHeld::held($grant) : TheGrantHeld::none();
    }

    public function keepTheGrant(StackId $stack, TheGrantIsFor $for, AGrant $grant): Kept
    {
        if (! $this->keeps) {
            return Kept::refused(WhySessionCannotBeKept::StoreWouldNotOpen);
        }

        $this->grants[$stack->stored()] = [$for, $grant];

        return Kept::safely();
    }

    public function letTheGrantGo(StackId $stack): Kept
    {
        if (! $this->keeps) {
            return Kept::refused(WhySessionCannotBeKept::StoreWouldNotOpen);
        }

        unset($this->grants[$stack->stored()]);

        return Kept::safely();
    }

    public function forgetTheStack(StackId $stack): Forgotten
    {
        if (! array_key_exists($stack->stored(), $this->grants)) {
            return Forgotten::nothing();
        }

        unset($this->grants[$stack->stored()]);

        return Forgotten::rows(1);
    }

    public function keepsAnythingOf(StackId $stack): bool
    {
        return array_key_exists($stack->stored(), $this->grants);
    }
}
