<?php

declare(strict_types=1);

namespace Modules\Dx\Api;

use Modules\Dx\Adapters\TheStoreThisRunKeeps;
use Modules\Kernel\Api\SecureStorage;
use Modules\Kernel\Api\Session;
use Modules\Vault\Api\PlatformKeychain;

/**
 * Somewhere a session may go that is not the device's keychain.
 *
 * Keeping nothing on the device is the reason this exists, said in as many words: a
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
 * The adapter is the shipped one, so the refusal when there is nowhere to
 * keep a session, the per-stack separation that is asked for, and the shape it
 * writes are all the real ones. Only the keychain underneath is replaced, which
 * is {@see ClientsThatReachNothing}'s argument at a different port.
 *
 * @implements StandsIn<SecureStorage>
 */
final readonly class ASessionThisRunKeeps implements StandsIn
{
    /**
     * What is held for each machine, and it is not a credential.
     *
     * Said in the value itself, because it travels: this is what goes out in
     * the `Authorization` header of every request the stand-in client answers,
     * and anybody reading a log of a run should be able to tell in a second
     * that nothing here reached a stack, which is why it can only ever be
     * this — a value that could authenticate against somebody's actual machine
     * is the thing that requirement refuses, and a made-up one that looked
     * plausible would be halfway there.
     */
    private const string NOT_A_CREDENTIAL = 'a-session-that-reached-no-stack';

    /**
     * The device is signed in to every machine it has been introduced to.
     *
     * Without this the app stops one frame past the list. `ADeviceAlreadyPaired`
     * makes the pairing, and a paired device with no session reaches exactly
     * the sign-in screen: every stack-scoped screen reads *you are signed out
     * of this stack* and draws nothing of its own. That is one branch of one
     * requirement, drawn eight times, in place of the application.
     *
     * **Every machine, including the one that refuses it.**
     * `AStandInStack::RefusingTheSession` answers `401`, and a refusal can only
     * be met by a device that had something to offer — with no session it
     * would never make the call, and the whole sequence (offer, refuse,
     * let go, sign in again) would be unreachable from a build meant to reach
     * everything.
     *
     * Written through the shipped adapter for {@see ADeviceAlreadyPaired}'s
     * reason: what the store holds is then the shape the adapter reads, rather
     * than this class's idea of it.
     */
    public function __construct(private TheStoreThisRunKeeps $store)
    {
        $keychain = new PlatformKeychain($this->store);

        foreach (AStandInStack::cases() as $standIn) {
            $keychain->keep($standIn->asAStack()->id(), Session::of(self::NOT_A_CREDENTIAL));
        }
    }

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
