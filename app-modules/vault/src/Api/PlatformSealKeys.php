<?php

declare(strict_types=1);

namespace Modules\Vault\Api;

use Lemonfiber\Native\Keeps;
use Lemonfiber\Native\WhenAValueMayBeRead;
use Lemonfiber\Native\WhyNothingWasKept;
use Modules\Kernel\Api\HoldsTheSealKeys;
use Modules\Kernel\Api\KeyHeld;
use Modules\Kernel\Api\KeyMaterial;
use Modules\Kernel\Api\SealKey;
use Modules\Kernel\Api\WhyNothingIsSealed;
use Modules\Vault\Internal\KeptUnder;

use function preg_match;
use function sodium_bin2hex;
use function sodium_hex2bin;

/**
 * The seal keys, in the device's own secure store.
 *
 * Beside {@see PlatformKeychain} and over the same store, and for the same
 * reason there is nowhere else here to put anything: every alternative — a
 * file, preferences, the framework's own key beside the database — is absent
 * from this class rather than guarded against.
 *
 * **Kept at the narrowest accessibility there is.** A key is readable only
 * while the device is unlocked, because nothing reads what the phone keeps in
 * the background. The store answers back which it gave, and that answer is not
 * read here, for the reason `PlatformKeychain` gives: nothing this class
 * decides turns on it.
 *
 * **A key is written as hex.** The store keeps strings and a key is bytes, and
 * `sodium_bin2hex` is the encoding whose time does not depend on the bytes it
 * is given. A value under one of these names that is not sixty-four hex digits
 * is a key that cannot be read, and it is replaced exactly as a missing one is.
 */
final readonly class PlatformSealKeys implements HoldsTheSealKeys
{
    /** A key as the store holds it: thirty-two bytes, as sixty-four lowercase hex digits. */
    private const string WRITTEN_AS = '/\A[0-9a-f]{64}\z/';

    public function __construct(private Keeps $store) {}

    public function readOrKeep(SealKey $which, KeyMaterial $fresh): KeyHeld
    {
        return $this->store->read($this->nameOf($which))->either(
            found: fn(string $written): KeyHeld => $this->readBack($which, $fresh, $written),
            nothing: fn(): KeyHeld => $this->kept($which, $fresh),
            refused: static fn(WhyNothingWasKept $why): KeyHeld => KeyHeld::refused(self::meaning($why)),
        );
    }

    /**
     * The key a stored value holds, or a new one kept in its place where it holds none.
     *
     * The value is matched before it is decoded, so the decoder is only ever
     * handed sixty-four hex digits and only ever answers thirty-two bytes.
     */
    private function readBack(SealKey $which, KeyMaterial $fresh, string $written): KeyHeld
    {
        if (preg_match(self::WRITTEN_AS, $written) !== 1) {
            return $this->kept($which, $fresh);
        }

        return KeyHeld::held(KeyMaterial::of(sodium_hex2bin($written)));
    }

    /** A new key, kept where the old one could not be read. */
    private function kept(SealKey $which, KeyMaterial $fresh): KeyHeld
    {
        return $this->store
            ->keep($this->nameOf($which), sodium_bin2hex($fresh->bytes()), WhenAValueMayBeRead::WhileUnlocked)
            ->either(
                done: static fn(): KeyHeld => KeyHeld::madeAfresh($fresh),
                refused: static fn(WhyNothingWasKept $why): KeyHeld => KeyHeld::refused(self::meaning($why)),
            );
    }

    /** What one of the store's refusals means to the seal. */
    private static function meaning(WhyNothingWasKept $why): WhyNothingIsSealed
    {
        return match ($why) {
            WhyNothingWasKept::NoStoreOnThisDevice => WhyNothingIsSealed::NoSecureStorage,
            WhyNothingWasKept::StoreWouldNotOpen => WhyNothingIsSealed::KeyUnreadable,
        };
    }

    /** The name one key is kept under. */
    private function nameOf(SealKey $which): string
    {
        $key = match ($which) {
            SealKey::TheDataKey => 'data',
            SealKey::TheStackKey => 'stack',
        };

        return KeptUnder::SealKeys->beneath($key);
    }
}
