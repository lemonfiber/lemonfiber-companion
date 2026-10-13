<?php

declare(strict_types=1);

namespace Modules\Operator\Internal;

use Closure;
use Modules\Kernel\Api\APhoneKeysName;
use Modules\Kernel\Api\ARefusalInItsWords;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\ThePhoneKeys;
use Modules\Kernel\Api\WhatWasFoundOfThePhoneKeys;
use Modules\Operator\Internal\Presenters\HowThePhoneKeysRead;
use Modules\Operator\Internal\ViewModels\ThePhoneKeysTurnedOutToBe;
use Native\Mobile\Edge\NativeComponent;

use function trim;

/**
 * The phone keys beside the keys for apps: where each lives, when it was registered and last used, and revoking one.
 *
 * A second reading of the stack, taken on a frame of its own. A revoke is asked
 * first, where the question can say that the phone holding the key can no
 * longer prove a mint with it and that a revoke cannot be undone; one the stack
 * refuses is said in its words.
 *
 * The screen that uses it takes {@see \Modules\Kernel\Api\KeepingPhoneKeys} as a protected `$keepingPhoneKeys`,
 * a {@see MintsFromThisPhone} as a protected `$mintsFromThisPhone`,
 * a {@see \Modules\Kernel\Api\Clock} as a private `$clock`, and reads its stack once a frame.
 *
 * @phpstan-require-extends NativeComponent
 */
trait KeepsThePhoneKeys
{
    /** The phone keys as they came back, once a frame has asked or a revoke answered. */
    public ?ThePhoneKeysTurnedOutToBe $phoneKeysFound = null;

    /** The name of the phone key the operator asked to revoke and has not confirmed yet, or empty. */
    public string $revokingAPhoneKey = '';

    /** Whether the phone keys wait for the next frame, because this one has read the stack already. */
    public function phoneKeysAreBeingRead(): bool
    {
        return ! $this->phoneKeys() instanceof ThePhoneKeysTurnedOutToBe;
    }

    /** The phone keys, or nothing while they wait for a frame of their own. */
    public function phoneKeys(): ?ThePhoneKeysTurnedOutToBe
    {
        if ($this->phoneKeysFound instanceof ThePhoneKeysTurnedOutToBe || ! $this->mayReadItsStack()) {
            return $this->phoneKeysFound;
        }

        return $this->phoneKeysFound = $this->askedOfThePhoneKeys(fn(Stack $stack, Session $session): WhatWasFoundOfThePhoneKeys => $this->keepingPhoneKeys->listedOn($stack, $session));
    }

    /**
     * The operator asked to revoke the phone key in that row, which is confirmed before anything is sent.
     *
     * The row and not the name, for {@see Screens\TheKeysAppsHold::askToRevoke()}'s
     * reason. A row it does not have, or one already revoked, asks nothing.
     */
    public function askToRevokeAPhoneKey(int $which): void
    {
        foreach ($this->phoneKeysFound->keys ?? [] as $at => $key) {
            if ($at === $which && $key->mayBeRevoked) {
                $this->revokingAPhoneKey = $key->name;
            }
        }
    }

    /** The operator kept the phone key. */
    public function keepThePhoneKey(): void
    {
        $this->revokingAPhoneKey = '';
    }

    /** The operator confirmed: revoke the phone key, and show the listing the stack answers with, or its refusal. */
    public function revokeThePhoneKey(): void
    {
        $name = $this->revokingAPhoneKey;
        $this->revokingAPhoneKey = '';

        if (trim($name) === '') {
            return;
        }

        $this->phoneKeysFound = $this->askedOfThePhoneKeys(fn(Stack $stack, Session $session): WhatWasFoundOfThePhoneKeys => $this->keepingPhoneKeys->revokedOn($stack, $session, APhoneKeysName::of($name)));
    }

    /**
     * Resume the session, ask the stack on it, and flatten what came back.
     *
     * @param Closure(Stack, Session): WhatWasFoundOfThePhoneKeys $asking
     */
    private function askedOfThePhoneKeys(Closure $asking): ThePhoneKeysTurnedOutToBe
    {
        $stack = $this->stack();
        $reads = new HowThePhoneKeysRead();
        $shown = $this->phoneKeysFound;

        return $this->storage->resume($stack->id())->either(
            held: fn(Session $session): ThePhoneKeysTurnedOutToBe => $asking($stack, $session)->either(
                found: fn(ThePhoneKeys $keys): ThePhoneKeysTurnedOutToBe => $reads->these($keys, $this->clock->now(), $this->mintsFromThisPhone->thisPhonesName($stack->id())),
                refused: fn(ARefusalInItsWords $why): ThePhoneKeysTurnedOutToBe => $reads->refused($why, $shown ?? $reads->these(ThePhoneKeys::of(), $this->clock->now(), '')),
                met: $this->lettingGoIfRefused($stack, $reads->met(...)),
            ),
            notHeld: static fn(): ThePhoneKeysTurnedOutToBe => $reads->signedOut(),
        );
    }
}
