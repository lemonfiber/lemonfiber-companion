<?php

declare(strict_types=1);

namespace Tests\Support\Fakes;

use function array_key_exists;
use function is_string;

use Lemonfiber\Native\PhoneKeys;
use Modules\Device\Api\PlatformPhoneKeys;
use Modules\Device\Api\SystemEntropy;
use Modules\Kernel\Api\WhyTheKeySignedNothing;
use Modules\Kernel\Api\WhyThisPhoneHoldsNoKey;
use Native\Mobile\Testing\FakeBridge;

use const SODIUM_BASE64_VARIANT_ORIGINAL;

use function sodium_bin2base64;

use Tests\Support\Catalogue;

/**
 * What a handset's phone key functions answer over the bridge, holding real P-256 keys made by OpenSSL.
 *
 * The words are written out rather than derived from the adapter's translation, so a mapping wrong
 * in both directions cannot agree with itself and pass.
 */
final class APhoneKeyOnAHandset
{
    /** @var array<string, AKeyPairInSoftware> */
    private array $held = [];

    /** @var list<string> */
    private array $reasons = [];

    /** @var list<string> */
    private array $aliases = [];

    private ?WhyTheKeySignedNothing $declined = null;

    private bool $stuck = false;

    private string $model = 'Galaxy A51';

    private function __construct(private readonly string $lives, private readonly ?WhyThisPhoneHoldsNoKey $none) {}

    /** An Android phone keeping keys in its trusted environment, attesting with a chain. */
    public static function onAnAndroidPhone(): self
    {
        return new self('tee', null);
    }

    /** An iPhone keeping keys in its Secure Enclave, attesting through App Attest. */
    public static function onAnIphone(): self
    {
        return new self('enclave', null);
    }

    /** A phone that says it keeps keys somewhere no stack accepts. */
    public static function inAPlaceNoStackAccepts(): self
    {
        return new self('software', null);
    }

    /** A phone that can keep no key, for this reason. */
    public static function keepingNone(WhyThisPhoneHoldsNoKey $why): self
    {
        return new self('', $why);
    }

    /** From now on, every prompt to sign ends this way. */
    public function declining(WhyTheKeySignedNothing $why): self
    {
        $this->declined = $why;

        return $this;
    }

    /** A phone that says it is called this, or nothing where it is empty. */
    public function called(string $model): self
    {
        $this->model = $model;

        return $this;
    }

    /**
     * This application's adapter, over a bridge scripted to answer the way this handset would.
     *
     * `FakeBridge` is `nativephp/mobile`'s own seam: the adapter runs the real calls, by the
     * names in the manifest, rather than something built to resemble them.
     */
    public function theAdapterOverIt(): PlatformPhoneKeys
    {
        FakeBridge::disable();
        FakeBridge::enable()
            ->respondTo('Lemonfiber.PhoneKey.Where', $this->where(...))
            ->respondTo('Lemonfiber.PhoneKey.Make', $this->make(...))
            ->respondTo('Lemonfiber.PhoneKey.Sign', $this->sign(...))
            ->respondTo('Lemonfiber.PhoneKey.Forget', $this->forget(...))
            ->respondTo('Lemonfiber.PhoneKey.Held', $this->held(...))
            ->respondTo('Lemonfiber.PhoneKey.Model', $this->model(...));

        return new PlatformPhoneKeys(new PhoneKeys(), Catalogue::words(), new SystemEntropy());
    }

    /** From now on, no key can be thrown away. */
    public function holdingOnToItsKeys(): self
    {
        $this->stuck = true;

        return $this;
    }

    /**
     * What `Lemonfiber.PhoneKey.Where` answers.
     *
     * @return array<string, string>
     */
    public function where(): array
    {
        return $this->none instanceof WhyThisPhoneHoldsNoKey
            ? ['outcome' => 'cannot', 'because' => $this->whyNone($this->none)]
            : ['outcome' => 'can', 'lives' => $this->lives];
    }

    /**
     * What `Lemonfiber.PhoneKey.Make` answers.
     *
     * @param array<string, mixed> $sent
     *
     * @return array<string, string|list<string>>
     */
    public function make(array $sent): array
    {
        if ($this->none instanceof WhyThisPhoneHoldsNoKey) {
            return ['outcome' => 'not_made', 'because' => $this->whyNone($this->none)];
        }

        $alias = $this->alias($sent);
        $pair = AKeyPairInSoftware::made();
        $this->held[$alias] = $pair;
        $this->aliases[] = $alias;
        $publicKey = sodium_bin2base64($pair->publicKey(), SODIUM_BASE64_VARIANT_ORIGINAL);

        return $this->lives === 'enclave'
            ? ['outcome' => 'made', 'lives' => $this->lives, 'public_key' => $publicKey, 'attestation' => $publicKey]
            : ['outcome' => 'made', 'lives' => $this->lives, 'public_key' => $publicKey, 'chain' => [$publicKey]];
    }

    /**
     * What `Lemonfiber.PhoneKey.Sign` answers.
     *
     * @param array<string, mixed> $sent
     *
     * @return array<string, string>
     */
    public function sign(array $sent): array
    {
        $this->reasons[] = is_string($sent['reason'] ?? null) ? $sent['reason'] : '';
        $pair = $this->held[$this->alias($sent)] ?? null;

        if ($this->declined instanceof WhyTheKeySignedNothing || ! $pair instanceof AKeyPairInSoftware) {
            return ['outcome' => 'not_signed', 'because' => $this->whyUnsigned($this->declined)];
        }

        $text = is_string($sent['text'] ?? null) ? $sent['text'] : '';

        return ['outcome' => 'signed', 'signature' => sodium_bin2base64($pair->signed($text), SODIUM_BASE64_VARIANT_ORIGINAL)];
    }

    /**
     * What `Lemonfiber.PhoneKey.Forget` answers.
     *
     * @param array<string, mixed> $sent
     *
     * @return array<string, string>
     */
    public function forget(array $sent): array
    {
        if ($this->stuck) {
            return ['outcome' => 'not_forgotten', 'because' => 'failed'];
        }

        $alias = $this->alias($sent);
        $held = array_key_exists($alias, $this->held);
        unset($this->held[$alias]);

        return ['outcome' => $held ? 'forgotten' : 'none_held'];
    }

    /**
     * What `Lemonfiber.PhoneKey.Held` answers.
     *
     * @param array<string, mixed> $sent
     *
     * @return array<string, string>
     */
    public function held(array $sent): array
    {
        return ['outcome' => $this->holds($this->alias($sent)) ? 'held' : 'none_held'];
    }

    /**
     * What `Lemonfiber.PhoneKey.Model` answers.
     *
     * @return array<string, string>
     */
    public function model(): array
    {
        return $this->model === '' ? ['outcome' => 'named'] : ['outcome' => 'named', 'model' => $this->model];
    }

    /**
     * The sentences the prompts were captioned with, in the order they were shown.
     *
     * @return list<string>
     */
    public function whatItSaid(): array
    {
        return $this->reasons;
    }

    /**
     * The aliases keys were made under, in the order they were made.
     *
     * @return list<string>
     */
    public function aliasesMade(): array
    {
        return $this->aliases;
    }

    /** Whether a key is held under this alias. */
    public function holds(string $alias): bool
    {
        return array_key_exists($alias, $this->held);
    }

    /** @param array<string, mixed> $sent */
    private function alias(array $sent): string
    {
        return is_string($sent['alias'] ?? null) ? $sent['alias'] : '';
    }

    private function whyNone(WhyThisPhoneHoldsNoKey $why): string
    {
        return match ($why) {
            WhyThisPhoneHoldsNoKey::NoHardware => 'no_hardware',
            WhyThisPhoneHoldsNoKey::NoBiometric => 'no_biometric',
            WhyThisPhoneHoldsNoKey::InSoftware => 'in_software',
            WhyThisPhoneHoldsNoKey::Unattested => 'unattested',
            WhyThisPhoneHoldsNoKey::Failed => 'failed',
        };
    }

    private function whyUnsigned(?WhyTheKeySignedNothing $why): string
    {
        return match ($why) {
            WhyTheKeySignedNothing::Cancelled => 'cancelled',
            WhyTheKeySignedNothing::Failed => 'failed',
            WhyTheKeySignedNothing::Gone, null => 'gone',
        };
    }
}
