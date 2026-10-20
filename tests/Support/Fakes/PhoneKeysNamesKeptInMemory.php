<?php

declare(strict_types=1);

namespace Tests\Support\Fakes;

use function array_key_exists;

use Modules\Kernel\Api\APhoneKeysName;
use Modules\Kernel\Api\Forgotten;
use Modules\Kernel\Api\KeepingThePhoneKeysName;
use Modules\Kernel\Api\Kept;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\ThePhoneKeysNameHeld;
use Modules\Kernel\Api\WhySessionCannotBeKept;

/** The names this phone's keys are registered under, in memory. */
final class PhoneKeysNamesKeptInMemory implements KeepingThePhoneKeysName
{
    /** @var array<string, APhoneKeysName> */
    private array $names = [];

    private function __construct(private readonly bool $keeps) {}

    /** A store that keeps what it is given. */
    public static function working(): self
    {
        return new self(keeps: true);
    }

    /** A store that will not open. */
    public static function refusing(): self
    {
        return new self(keeps: false);
    }

    public function nameOn(StackId $stack): ThePhoneKeysNameHeld
    {
        return array_key_exists($stack->stored(), $this->names)
            ? ThePhoneKeysNameHeld::named($this->names[$stack->stored()])
            : ThePhoneKeysNameHeld::none();
    }

    public function keepTheName(StackId $stack, APhoneKeysName $name): Kept
    {
        if (! $this->keeps) {
            return Kept::refused(WhySessionCannotBeKept::StoreWouldNotOpen);
        }

        $this->names[$stack->stored()] = $name;

        return Kept::safely();
    }

    public function letTheNameGo(StackId $stack): Kept
    {
        if (! $this->keeps) {
            return Kept::refused(WhySessionCannotBeKept::StoreWouldNotOpen);
        }

        unset($this->names[$stack->stored()]);

        return Kept::safely();
    }

    public function forgetTheStack(StackId $stack): Forgotten
    {
        if (! $this->keepsAnythingOf($stack)) {
            return Forgotten::nothing();
        }

        unset($this->names[$stack->stored()]);

        return Forgotten::rows(1);
    }

    public function keepsAnythingOf(StackId $stack): bool
    {
        return array_key_exists($stack->stored(), $this->names);
    }
}
