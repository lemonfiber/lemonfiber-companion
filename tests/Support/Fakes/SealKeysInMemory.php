<?php

declare(strict_types=1);

namespace Tests\Support\Fakes;

use function array_key_exists;

use Modules\Kernel\Api\HoldsTheSealKeys;
use Modules\Kernel\Api\KeyHeld;
use Modules\Kernel\Api\KeyMaterial;
use Modules\Kernel\Api\SealKey;
use Modules\Kernel\Api\WhyNothingIsSealed;

/**
 * The two seal keys, held for as long as a test runs.
 *
 * Held to the same contract as {@see \Modules\Vault\Api\PlatformSealKeys}, so
 * that a seal tested over this is tested over a store that behaves like the
 * platform's: a key is made once and handed back after, and both refusals are
 * reachable, because a phone with no secure storage is the case the port
 * exists to report.
 *
 * Written by hand rather than mocked, so a change to the port fails to compile
 * here rather than drifting.
 */
final class SealKeysInMemory implements HoldsTheSealKeys
{
    /** @var array<string, KeyMaterial> the key's case name => the key */
    private array $held = [];

    private function __construct(private readonly ?WhyNothingIsSealed $refusing) {}

    /** Secure storage that works, holding no key yet: a first launch. */
    public static function empty(): self
    {
        return new self(null);
    }

    /** Secure storage that works and already holds both keys. */
    public static function holding(KeyMaterial $data, KeyMaterial $stack): self
    {
        $store = new self(null);
        $store->held[SealKey::TheDataKey->name] = $data;
        $store->held[SealKey::TheStackKey->name] = $stack;

        return $store;
    }

    /** A device with no secure storage at all. */
    public static function withNoSecureStorage(): self
    {
        return new self(WhyNothingIsSealed::NoSecureStorage);
    }

    /** Secure storage that is there and will not open. */
    public static function thatWillNotOpen(): self
    {
        return new self(WhyNothingIsSealed::KeyUnreadable);
    }

    public function readOrKeep(SealKey $which, KeyMaterial $fresh): KeyHeld
    {
        if ($this->refusing instanceof WhyNothingIsSealed) {
            return KeyHeld::refused($this->refusing);
        }

        if (array_key_exists($which->name, $this->held)) {
            return KeyHeld::held($this->held[$which->name]);
        }

        $this->held[$which->name] = $fresh;

        return KeyHeld::madeAfresh($fresh);
    }

    /** The key goes, as it does on a device restored from a backup: for a test to arrange. */
    public function loses(SealKey $which): self
    {
        unset($this->held[$which->name]);

        return $this;
    }
}
