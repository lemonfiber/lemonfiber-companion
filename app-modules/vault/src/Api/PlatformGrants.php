<?php

declare(strict_types=1);

namespace Modules\Vault\Api;

use Lemonfiber\Native\Keeps;
use Lemonfiber\Native\WhenAValueMayBeRead;
use Lemonfiber\Native\WhyNothingWasKept;
use Lemonfiber\Native\Wrote;
use Modules\Kernel\Api\AGrant;
use Modules\Kernel\Api\Forgotten;
use Modules\Kernel\Api\KeepingTheGrant;
use Modules\Kernel\Api\Kept;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\TheGrantHeld;
use Modules\Kernel\Api\TheGrantIsFor;
use Modules\Vault\Internal\KeptUnder;
use Modules\Vault\Internal\TheGrantAsWritten;
use Modules\Vault\Internal\WhatARefusalToKeepMeans;
use Modules\Vault\Internal\WhetherAnythingIsHeld;

/**
 * This device's grant on each stack, kept in the platform's own store.
 *
 * **The keychain, at the narrowest accessibility there is**, as
 * {@see PlatformKeychain} keeps a session: nothing in this application plays a
 * member's titles with the phone locked before it was first unlocked.
 *
 * **A key per stack**, so two stacks never share a grant, and a new grant
 * replaces the one before it on its own stack and nowhere else. **Kept with
 * whom it was for**, so a member signing in where another played is not
 * handed the other's grant, and a device id drawn afresh does not reuse one.
 *
 * **A grant is kept in a shape** ({@see TheGrantAsWritten}), and one this build
 * cannot read is no grant: that costs the member one more ask of the core,
 * which answers a new one and ends the old.
 */
final readonly class PlatformGrants implements KeepingTheGrant
{
    public function __construct(private Keeps $store) {}

    public function theGrantOn(StackId $stack, TheGrantIsFor $for): TheGrantHeld
    {
        return $this->store->read($this->keyFor($stack))->either(
            found: static fn(string $written): TheGrantHeld => TheGrantAsWritten::read($written, $for),
            nothing: TheGrantHeld::none(...),
            refused: TheGrantHeld::none(...),
        );
    }

    public function keepTheGrant(StackId $stack, TheGrantIsFor $for, AGrant $grant): Kept
    {
        return $this->kept($this->store->keep($this->keyFor($stack), TheGrantAsWritten::written($for, $grant), WhenAValueMayBeRead::WhileUnlocked));
    }

    public function letTheGrantGo(StackId $stack): Kept
    {
        return $this->kept($this->store->forget($this->keyFor($stack)));
    }

    public function forgetTheStack(StackId $stack): Forgotten
    {
        $key = $this->keyFor($stack);

        return $this->store->read($key)->either(
            found: fn(): Forgotten => $this->store->forget($key)->either(
                done: static fn(): Forgotten => Forgotten::rows(1),
                refused: static fn(): Forgotten => Forgotten::nothing(),
            ),
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

    private function keyFor(StackId $stack): string
    {
        return KeptUnder::Grant->beneath($stack->stored());
    }
}
