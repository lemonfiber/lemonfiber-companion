<?php

declare(strict_types=1);

namespace Modules\Vault\Api;

use Lemonfiber\Native\Keeps;
use Lemonfiber\Native\WhenAValueMayBeRead;
use Lemonfiber\Native\WhyNothingWasKept;
use Lemonfiber\Native\Wrote;
use Modules\Kernel\Api\APhoneKeysName;
use Modules\Kernel\Api\Forgotten;
use Modules\Kernel\Api\KeepingThePhoneKeysName;
use Modules\Kernel\Api\Kept;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\ThePhoneKeysNameHeld;
use Modules\Vault\Internal\KeptUnder;
use Modules\Vault\Internal\WhatARefusalToKeepMeans;
use Modules\Vault\Internal\WhetherAnythingIsHeld;

use function trim;

/**
 * The name this phone's key is registered under on each stack, kept in the platform's own store.
 *
 * **A key per stack**, so a name kept for one stack never names the key on another. A name the
 * store will not give back is none: the phone then registers a new key under a new name.
 */
final readonly class PlatformPhoneKeysNames implements KeepingThePhoneKeysName
{
    public function __construct(private Keeps $store) {}

    public function nameOn(StackId $stack): ThePhoneKeysNameHeld
    {
        return $this->store->read($this->keyFor($stack))->either(
            found: static fn(string $written): ThePhoneKeysNameHeld => trim($written) === ''
                ? ThePhoneKeysNameHeld::none()
                : ThePhoneKeysNameHeld::named(APhoneKeysName::of($written)),
            nothing: ThePhoneKeysNameHeld::none(...),
            refused: ThePhoneKeysNameHeld::none(...),
        );
    }

    public function keepTheName(StackId $stack, APhoneKeysName $name): Kept
    {
        return $this->kept($this->store->keep($this->keyFor($stack), $name->shown(), WhenAValueMayBeRead::WhileUnlocked));
    }

    public function letTheNameGo(StackId $stack): Kept
    {
        return $this->kept($this->store->forget($this->keyFor($stack)));
    }

    public function forgetTheStack(StackId $stack): Forgotten
    {
        $key = $this->keyFor($stack);

        return $this->store->read($key)->either(
            found: fn(): Forgotten => $this->forgotten($this->store->forget($key)),
            nothing: static fn(): Forgotten => Forgotten::nothing(),
            refused: static fn(): Forgotten => Forgotten::nothing(),
        );
    }

    public function keepsAnythingOf(StackId $stack): bool
    {
        return $this->store->read($this->keyFor($stack))->either(
            found: static fn(): WhetherAnythingIsHeld => WhetherAnythingIsHeld::itIs(),
            nothing: static fn(): WhetherAnythingIsHeld => WhetherAnythingIsHeld::itIsNot(),
            refused: static fn(): WhetherAnythingIsHeld => WhetherAnythingIsHeld::itIs(),
        )->held;
    }

    /** What the store's answer to a write comes to. */
    private function kept(Wrote $wrote): Kept
    {
        return $wrote->either(
            done: static fn(): Kept => Kept::safely(),
            refused: static fn(WhyNothingWasKept $why): Kept => Kept::refused(WhatARefusalToKeepMeans::of($why)),
        );
    }

    /** What letting the name go came to. */
    private function forgotten(Wrote $wrote): Forgotten
    {
        return $wrote->either(
            done: static fn(): Forgotten => Forgotten::rows(1),
            refused: static fn(): Forgotten => Forgotten::nothing(),
        );
    }

    private function keyFor(StackId $stack): string
    {
        return KeptUnder::PhoneKeysName->beneath($stack->stored());
    }
}
