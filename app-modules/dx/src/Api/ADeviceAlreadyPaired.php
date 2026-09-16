<?php

declare(strict_types=1);

namespace Modules\Dx\Api;

use Modules\Dx\Adapters\TheStoreThisRunKeeps;
use Modules\Kernel\Api\Address;
use Modules\Kernel\Api\Fingerprint;
use Modules\Kernel\Api\Nonce;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\StackName;
use Modules\Kernel\Api\Stacks;
use Modules\Vault\Api\PlatformStacks;

use function str_repeat;

/**
 * A device that has already been introduced to a machine.
 *
 * The other half of `N1-R57`, and the half without which the first is worth
 * little: {@see AStackThatIsNotThere} makes a stack answer, and every screen
 * that asks one anything sits behind a pairing. A device holding none reaches
 * exactly one frame of this application — the first run — so standing in for
 * the stack alone leaves the rest of it as unreachable as it was.
 *
 * **Two affordances rather than one, on purpose.** Folding them together would
 * make the first run the one thing that could never be looked at, and it is the
 * sequence most worth looking at: `N1-R54` builds it a step at a time and
 * `N1-R56` says a paired device never sees it again. Kept apart, a stand-in
 * stack with no pairing is the first run with something behind it, and both
 * together is the app an operator uses every day.
 *
 * **The pairing is real, and only the keychain is not.** What answers the port
 * is `PlatformStacks` — the shipped adapter, writing the JSON it always writes
 * and applying `Configured::with()`'s re-pairing rule — over
 * {@see TheStoreThisRunKeeps}, which holds it in this process. So pairing
 * another machine while this is on behaves exactly as it does on a real device
 * for as long as the app is open, and leaves nothing behind when it closes.
 * `N1-R60` asks for the second half in as many words.
 *
 * @implements StandsIn<Stacks>
 */
final readonly class ADeviceAlreadyPaired implements StandsIn
{
    /**
     * A name nobody could mistake for a machine of theirs.
     *
     * The same argument {@see \Modules\Dx\Internal\WhatAStackWouldSay} makes
     * about payloads: anybody looking at a screen should be able to tell in a
     * second that they are looking at a stand-in, and a plausible household
     * name is exactly what would stop them.
     */
    private const string CALLED = 'Stand-in';

    /**
     * Reserved by RFC 2606, so it resolves nowhere.
     *
     * `N1-R60` refuses pairing material that could reach a real stack, and an
     * address that cannot be resolved is the strongest form of that: were the
     * stand-in client ever to miss a request, the failure would be a name that
     * does not exist rather than a connection to somebody's actual machine.
     */
    private const string AT = 'https://a-stack-that-is-not-there.invalid:8443';

    /**
     * How many bytes a SHA-256 digest is.
     *
     * Named because `D6` is right that a bare 32 beside a repeat says nothing:
     * the reader has to know both that the pin is SHA-256 and that the written
     * form `N1-R18` fixes is hex, which doubles it. Written as the byte count
     * rather than the character count, because that is the fact — the doubling
     * belongs to the spelling and is visible in the pair it repeats.
     */
    private const int A_SHA256 = 32;

    /**
     * The store, shared with every other stand-in that sits on one.
     *
     * Shared rather than built here, because the device's keychain is one
     * place: `Stacks`, `SecureStorage` and `Verdicts` are three ports over the
     * same store, and three stand-ins each holding their own would behave
     * differently from the thing they stand in for in a way that is hard to
     * see and easy to be misled by — a session kept under a stack the session
     * store has never heard of.
     */
    public function __construct(private TheStoreThisRunKeeps $store)
    {
        // Written through the adapter rather than into the store directly, so
        // the shape the store holds is the shape the adapter reads — a seeded
        // value assembled here would be this class's idea of that shape, which
        // is the fixture-written-by-its-reader problem one layer down.
        new PlatformStacks($this->store)->remember($this->theOneItStandsIn());
    }

    public function insteadOf(): string
    {
        return Stacks::class;
    }

    /**
     * Built per call over a store that is not, which is the whole arrangement.
     *
     * The port promises a fresh adapter and the real binding is not a
     * singleton, so this answers the same way. What persists is the store
     * behind it — as the keychain persists behind the real one — which is what
     * makes a pairing made during the run still there a screen later.
     */
    public function which(): Stacks
    {
        return new PlatformStacks($this->store);
    }

    /** The machine this device is pretending to have been introduced to. */
    private function theOneItStandsIn(): Stack
    {
        return Stack::of(
            StackId::of(Nonce::of(str_repeat('s', Nonce::SHORTEST))),
            StackName::of(self::CALLED),
            Address::of(self::AT),
            // Sixty-four hex characters, which is the written form `N1-R18`
            // fixes, and a value no certificate has.
            Fingerprint::of(str_repeat('ab', self::A_SHA256)),
        );
    }
}
