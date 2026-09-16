<?php

declare(strict_types=1);

namespace Modules\Dx\Api;

use Modules\Dx\Adapters\TheStoreThisRunKeeps;
use Modules\Kernel\Api\SecureStorage;
use Modules\Vault\Api\PlatformKeychain;

/**
 * Somewhere a session may go that is not the device's keychain.
 *
 * `N1-R60` is the reason this exists and it says so in as many words: a
 * stand-in MUST NOT write a credential, a session or pairing material to the
 * device's store. Without this it would. Signing in to the stand-in stack runs
 * the real sign-in screen, which keeps what came back — and what came back was
 * assembled from the contract, so a fabricated session would be written into
 * the operator's actual keychain and outlive the run that made it.
 *
 * Nothing about that is dangerous on its own. It is residue, and residue from a
 * development affordance is exactly the thing a requirement written before the
 * affordance existed was trying to prevent: turning stand-ins on has to leave
 * the device as it found it.
 *
 * The adapter is the shipped one, so `N4-R6`'s refusal when there is nowhere to
 * keep a session, the per-stack separation `N1-R11` asks for and the shape it
 * writes are all the real ones. Only the keychain underneath is replaced, which
 * is {@see ClientsThatReachNothing}'s argument at a different port.
 *
 * @implements StandsIn<SecureStorage>
 */
final readonly class ASessionThisRunKeeps implements StandsIn
{
    public function __construct(private TheStoreThisRunKeeps $store) {}

    public function insteadOf(): string
    {
        return SecureStorage::class;
    }

    /**
     * Built per call, over a store that is not.
     *
     * The real binding is deliberately not a singleton — a keychain handle held
     * for the life of a long-running app is how one unlocked at launch goes on
     * reading as unlocked after the device has locked — so this answers the
     * same way and the difference stays where it belongs.
     */
    public function which(): SecureStorage
    {
        return new PlatformKeychain($this->store);
    }
}
