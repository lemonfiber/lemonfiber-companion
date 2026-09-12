<?php

declare(strict_types=1);

namespace Tests\Support\Fakes;

use function array_key_exists;
use function array_keys;

use Native\Mobile\SecureStorage as Platform;
use Native\Mobile\SecureStorageAccessibility;
use Native\Mobile\SecureStorageResult;
use Native\Mobile\SecureStorageStatus;

/**
 * A stand-in for the platform's own store, written by hand.
 *
 * `G1` forbids *mocking* a type we do not own, and this is not one: it is a
 * subclass with the three methods written out, which is the same thing
 * `FrozenClock` is to a clock. The distinction matters — a mock asserts on calls
 * and drifts silently when the real class changes, and this fails to compile.
 *
 * It exists because `PlatformKeychain` cannot otherwise be run at all. There is
 * no Keychain behind a PHP process on a laptop, so without this the adapter is
 * a file nothing executes, and the three things it actually decides — which key
 * a stack gets, which refusal a failure is, that forgetting ignores the
 * result — go unchecked until somebody holds a phone.
 */
final class APlatformStore extends Platform
{
    /** @var array<string, string> */
    private array $held = [];

    private function __construct(
        private readonly SecureStorageStatus $answering,
        private readonly bool $accepting,
    ) {}

    /** A store that works, on a device that has one. */
    public static function working(): self
    {
        return new self(SecureStorageStatus::NotFound, accepting: true);
    }

    /** A device with no secure store at all. */
    public static function absent(): self
    {
        return new self(SecureStorageStatus::Unavailable, accepting: false);
    }

    /** A store that is present and will not accept a write. */
    public static function refusing(): self
    {
        return new self(SecureStorageStatus::Failed, accepting: false);
    }

    public function set(string $key, ?string $value, ?SecureStorageAccessibility $accessibility = null): bool
    {
        if (! $this->accepting || $value === null) {
            return false;
        }

        $this->held[$key] = $value;

        return true;
    }

    public function get(string $key): ?string
    {
        return $this->held[$key] ?? null;
    }

    public function read(string $key): SecureStorageResult
    {
        if ($this->answering === SecureStorageStatus::Unavailable) {
            return new SecureStorageResult(SecureStorageStatus::Unavailable);
        }

        return array_key_exists($key, $this->held)
            ? new SecureStorageResult(SecureStorageStatus::Found, $this->held[$key])
            : new SecureStorageResult($this->answering);
    }

    public function delete(string $key): bool
    {
        unset($this->held[$key]);

        return true;
    }

    /**
     * Which keys this store is holding — for a test to check one per stack.
     *
     * @return list<string>
     */
    public function keysHeld(): array
    {
        return array_keys($this->held);
    }
}
