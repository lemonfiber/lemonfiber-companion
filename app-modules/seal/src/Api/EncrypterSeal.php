<?php

declare(strict_types=1);

namespace Modules\Seal\Api;

use function hash_hmac;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Encryption\Encrypter;
use Modules\Kernel\Api\Entropy;
use Modules\Kernel\Api\HoldsTheSealKeys;
use Modules\Kernel\Api\KeyMaterial;
use Modules\Kernel\Api\Sealed;
use Modules\Kernel\Api\SealedPayload;
use Modules\Kernel\Api\SealedStack;
use Modules\Kernel\Api\Sealing;
use Modules\Kernel\Api\SealKey;
use Modules\Kernel\Api\SealStanding;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\Unsealed;
use Modules\Kernel\Api\Unsealing;
use Modules\Kernel\Api\WhyNothingIsSealed;

/**
 * {@see Sealed}, answered by Laravel's encrypter under the phone's own data key.
 *
 * The one class that encrypts what the phone keeps, and the one that hashes a
 * stack's identity. Both keys arrive through {@see HoldsTheSealKeys}, which
 * keeps them in the platform's secure storage, and a key that has to be made
 * is drawn from {@see Entropy}. The framework's own key and the `Crypt` facade
 * are never used: on Android that key is a plain file beside the database, and
 * a seal under it stops only somebody opening the database by hand.
 *
 * **AES-256-GCM.** An authenticated cipher, so a payload altered anywhere does
 * not decrypt to something else — it does not decrypt at all. The encrypter
 * draws a fresh IV for every seal, which is why two seals of one value differ.
 *
 * **Strings in and strings out.** `encryptString` and `decryptString`, never
 * `encrypt` and `decrypt`: those serialise and unserialise, and unserialising
 * whatever a store hands back is the door an object walks in through.
 *
 * **A stack is its HMAC-SHA256 under the stack key**, a separate key from the
 * one that seals, so the hash that finds a stack's rows is no help in reading
 * them.
 */
final readonly class EncrypterSeal implements Sealed
{
    /** The cipher every payload is sealed with. */
    private const string CIPHER = 'aes-256-gcm';

    /** The hash a stack's identity is keyed under. */
    private const string HASHED_WITH = 'sha256';

    public function __construct(private HoldsTheSealKeys $keys, private Entropy $entropy) {}

    public function standing(): SealStanding
    {
        return $this->standingOf(SealKey::TheDataKey)->beside($this->standingOf(SealKey::TheStackKey));
    }

    public function seal(Unsealed $value): Sealing
    {
        return $this->keys->readOrKeep(SealKey::TheDataKey, $this->entropy->aKey())->either(
            held: static fn(KeyMaterial $key): Sealing => self::sealedUnder($key, $value),
            madeAfresh: static fn(KeyMaterial $key): Sealing => self::sealedUnder($key, $value),
            refused: static fn(WhyNothingIsSealed $why): Sealing => Sealing::refused($why),
        );
    }

    public function open(SealedPayload $payload): Unsealing
    {
        return $this->keys->readOrKeep(SealKey::TheDataKey, $this->entropy->aKey())->either(
            held: static fn(KeyMaterial $key): Unsealing => self::openedUnder($key, $payload),
            // A key made just now has sealed nothing yet, so nothing it is
            // handed can open under it.
            madeAfresh: static fn(): Unsealing => Unsealing::unreadable(),
            refused: static fn(): Unsealing => Unsealing::unreadable(),
        );
    }

    public function stack(StackId $stack): SealedStack
    {
        $fresh = $this->entropy->aKey();

        return $this->keys->readOrKeep(SealKey::TheStackKey, $fresh)->either(
            held: static fn(KeyMaterial $key): SealedStack => self::hashedUnder($key, $stack),
            madeAfresh: static fn(KeyMaterial $key): SealedStack => self::hashedUnder($key, $stack),
            // Under the key that was offered and not kept, so the hash is
            // kept nowhere either and matches no row.
            refused: static fn(): SealedStack => self::hashedUnder($fresh, $stack),
        );
    }

    /** Where one key stands, as the answer about both is built from. */
    private function standingOf(SealKey $which): SealStanding
    {
        return $this->keys->readOrKeep($which, $this->entropy->aKey())->either(
            held: static fn(): SealStanding => SealStanding::Held,
            madeAfresh: static fn(): SealStanding => SealStanding::MadeAfresh,
            refused: static fn(): SealStanding => SealStanding::Unavailable,
        );
    }

    private static function sealedUnder(KeyMaterial $key, Unsealed $value): Sealing
    {
        return Sealing::sealed(SealedPayload::of(self::encrypter($key)->encryptString($value->inTheClear())));
    }

    /**
     * What a payload opens to, or that it does not.
     *
     * The encrypter's refusal is caught here and nowhere else, and becomes the
     * one answer a caller has: a payload altered, sealed under another key, or
     * never a payload at all is unreadable, and none of the three is a fault.
     */
    private static function openedUnder(KeyMaterial $key, SealedPayload $payload): Unsealing
    {
        try {
            return Unsealing::opened(Unsealed::of(self::encrypter($key)->decryptString($payload->forTheStore())));
        } catch (DecryptException) {
            return Unsealing::unreadable();
        }
    }

    private static function hashedUnder(KeyMaterial $key, StackId $stack): SealedStack
    {
        return SealedStack::of(hash_hmac(self::HASHED_WITH, $stack->stored(), $key->bytes()));
    }

    private static function encrypter(KeyMaterial $key): Encrypter
    {
        return new Encrypter($key->bytes(), self::CIPHER);
    }
}
